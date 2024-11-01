<?php
/*
Plugin Name: Vitrine Frugar
Plugin URI: 
Version: 1.0
Description: Use o Vitrine Frugar para criar e exibir em seu blog vitrines de produtos com recomenda&ccedil;ões feitas por você aos seus leitores e ganhe dinheiro com isso
Author: Frugar
Text Domain: wp-vitrine-frugar
License: GPL2
*/
if ( is_admin() ){
	
	add_action('init', array('WPVitrineFrugar', 'init'));
	register_activation_hook(__FILE__, array('WPVitrineFrugar', 'activate'));
	register_deactivation_hook(__FILE__, array('WPVitrineFrugar', 'deactivate'));
/**
 * 
 * WP Vitrine Frugar, singleton
 * @author Frugar
 *
 */
class WPVitrineFrugar{
	/**
	 * configuration for the plugin
	 * 
	 * @param string $key option name
	 * @return config value
	 */
	protected static function getConfig($key){
		static $config = false;
		if($config === false){
			$config = array(
				'option_page_title' => __('WP Vitrine Frugar', 'wp-vitrine-frugar')
				, 'menu_title' => __('WP Vitrine Frugar', 'wp-vitrine-frugar')
				, 'option_name' => 'wp_vitrine_frugar'
				, 'menu_capability' => 'manage_options'
				, 'button_url' => plugins_url('images/embed-button.png', __FILE__)
				, 'loading_icon_url' => admin_url('images/loading.gif')
				, 'show_in_both_modes' => false
				, 'supported_attributes' => array('url', 'maxwidth', 'maxheight')
			);
		}
		return apply_filters('wp_vitrine_frugar_config_' . $key, $config[$key]);
	}
	
	protected static $instance = null;
	protected $options = null;
	private function __construct() {
		$this->options = get_option(self::getConfig('option_name'));
	}
	private function __clone() {}
 	public static function getInstance() {
        if ( is_null(self::$instance) ) {
            self::$instance = new self;
        }
        return self::$instance;
    }
	
	/**
	 * i18n support
	 */
	protected static function loadTranslation() {
		load_plugin_textdomain( 'wp-vitrine-frugar', false, self::getConfig('i18n_path'));
	}
	
	/**
	 * 
	 * plugin activation hook
	 */
	public static function activate() {
		$name = self::getConfig('option_name');
		if(!is_array(get_option($name))){
			update_option($name, array(
				'show_in_media_toolbar' => true
				, 'show_in_editor_toolbar' => false
				, 'show_in_fullscreen' => true
				, 'keep_settings' => false
			));
		}
	}
	
	/**
	 * 
	 * plugin deactivation hook
	 */
	public static function deactivate() {
		if(self::getInstance()->getOption('keep_settings') !== true){
			delete_option(self::getConfig('option_name'));
		}
	}
	
	/**
	 * 
	 * setup buttons depending on selected settings
	 */
	protected function setupButtons() {
		add_action('admin_print_styles', array(__CLASS__, '_printStylesheet'));
		
		if($this->hasToShowMediaButton()){
			add_action('media_buttons_context', array(__CLASS__, 'addToMediaButtons'));
		}
		if(
			$this->hasToShowEditorButton()
			|| $this->hasToShowFullscreenButton()
		){
			add_filter("mce_external_plugins", array(__CLASS__, '_addTinyMCEPlugin'));
			add_filter("admin_print_footer_scripts", array(__CLASS__, '_printJSIconsURL'));
		}
		
		if($this->hasToShowEditorButton()){
			add_filter('mce_buttons', array(__CLASS__, 'addToEditorButtons'));
		}
		if($this->hasToShowFullscreenButton()){
			add_filter('wp_fullscreen_buttons', array(__CLASS__, 'addToFullscreenButtons'));
		}
	}
	
	/**
	 * 
	 * setting up all required hooks
	 */
	public static function init(){
		self::loadTranslation();
		do_action('wp_vitrine_frugar_init');
		add_action('admin_menu', array(__CLASS__, 'addAdminMenu'));
		add_action('admin_init', array(__CLASS__, 'registerSettings'));
		add_action('wp_ajax_wp_vitrine_frugar_preview', array(__CLASS__, 'doEmbedPreview'));
		
		if(self::hasToShowAnyButton()){
			self::getInstance()->setupButtons();
		}
	}
	
	/**
	 * 
	 * Check, whether we should show any button to user or not?
	 * Wordpress filters: wp_vitrine_frugar_show_any_button with current decision passed
	 * @return boolean decision
	 */
	protected static function hasToShowAnyButton(){
		static $hasToShow = null;
		if($hasToShow === null){
			global $pagenow, $typenow;
			$type = $typenow == '' ? 'post' : $typenow;
			$hasToShow = apply_filters('wp_vitrine_frugar_show_any_button', 
				get_user_option('rich_editing') == 'true'
				&& ($pagenow === "post.php" || $pagenow === "post-new.php") 
				&& (
					($type === "post" && current_user_can('edit_posts'))
					|| ($type === "page" && current_user_can('edit_pages'))
				)
			);
		}
		return $hasToShow;
	}
	
	/**
	 * 
	 * Check, whether we should show our button in media toolbar?
	 * @return boolean decision
	 */
	protected function hasToShowMediaButton(){
		return $this->getOption('show_in_media_toolbar') === true;
	}
	
	/**
	 * 
	 * Check, whether we should show our button in editor toolbar?
	 * @return boolean decision
	 */
	protected function hasToShowEditorButton(){
		return $this->getOption('show_in_editor_toolbar') === true;
	}
	
	/**
	 * 
	 * Check, whether we should show our button in fullscreen mode?
	 * @return boolean decision
	 */
	protected function hasToShowFullscreenButton(){
		return $this->getOption('show_in_fullscreen') === true;
	}
	
	/**
	 * 
	 * Returns option saved in options array for plugin
	 * @param string $key option to get
	 * @return option value
	 */
	protected function getOption($key){
		return $this->options[$key];
	}
	
    /**
     * 
     * returns url for button on the edit post page.
     * 
     * @return icon url
     */
	public static function getIconURL($type) {
		return self::getConfig('button_icon_' . $type);
	}
	
	/**
	 * generate and return html for embed button
	 * @param string $type type of embed (image, link, video)
	 * @param string $title title for button
	 * @return html code 
	 */
	protected static function getEmbedButton($title) {
		return '<a href="#" id="embed_button" title="' . $title . '" onclick="tinymce.execCommand(\'wpvitrinefrugar\');return false;"><img src="' . esc_url( self::getConfig('button_url') ) . '" alt="' . $title . '"/></a>';
	}
	
	/**
	 * 
	 * Appends easy embed button(s) to media buttons context
	 * @param string $html media buttons html
	 * @return html with media buttons block appended
	 */
	public static function addToMediaButtons($html) {
		return $html . self::getEmbedButton(__('Vitrine Frugar', 'wp-vitrine-frugar'))
		; 
	}
	
	/**
	 * 
	 * Appends easy embed button to one of tinymce toolbars 
	 * @param array $buttons already registered buttons
	 * @return $buttons with our button appended
	 */
	public static function addToEditorButtons($buttons) {
		array_push($buttons, 'separator', 'wpvitrinefrugar');
		return $buttons;
	}
	
	/**
	 * 
	 * Add our plugin to tinymce plugin array
	 * @param Array $plugin_array already registered plugins
	 * @return $plugin_array with our plugin appended
	 */
	public static function _addTinyMCEPlugin($plugin_array) {
		$plugin_array['wpvitrinefrugar'] = plugins_url('tinymce/editor_plugin.js', __FILE__);
		return $plugin_array;
	}
	
	/**
	 * 
	 * Appends easy embed button(s) to fullscreen mode (only called if editor buttons is enabled)
	 * @param array $buttons Array of buttons currently used
	 * @return $buttons Array with our buttons appended
	 */
	public static function addToFullscreenButtons($buttons) {
		$buttons[] = 'separator';
		$buttons['wpvitrinefrugar'] = array(
			'title' => __('Vitrine Frugar', 'wp-vitrine-frugar')
			, 'onclick' => 'tinymce.execCommand(\'wpvitrinefrugar\');'
			, 'both' => self::getConfig('show_in_both_modes')
		);
		return $buttons;
	}
	
	/**
	 * 
	 * Adds plugin configuration page to admin menu
	 * 
	 * @return The resulting page's hook_suffix
	 */
	public static function addAdminMenu(){
		return add_options_page(
			self::getConfig('option_page_title')
			, self::getConfig('menu_title')
			, self::getConfig('menu_capability')
			, __FILE__
			, array(__CLASS__, 'getOptionsPage')
		);
	}
	
	/**
	 * register plugin configuration settings
	 */
	public static function registerSettings(){
		register_setting(
			'wp_vitrine_frugar_options'
			, 'wp_vitrine_frugar'
			, array(
				__CLASS__
				, '_validateOption'
			)
		);
		add_settings_section(
			'wp_vitrine_frugar_main'
			, __('Settings', 'wp-vitrine-frugar')
			, array(__CLASS__, '_getOptionsSection'	)
			, 'wp-vitrine-frugar'
		);
		add_settings_field(
			'wp_vitrine_frugar_show'
			, __('Where to show', 'wp-vitrine-frugar')
			, array(__CLASS__, '_getShowOption')
			, 'wp-vitrine-frugar'
			, 'wp_vitrine_frugar_main'
		);
		add_settings_field(
			'wp_vitrine_frugar_keep'
			, __('Keep settings', 'wp-vitrine-frugar')
			, array(__CLASS__, '_getKeepOption')
			, 'wp-vitrine-frugar'
			, 'wp_vitrine_frugar_main'
		);
	}
	
	/**
	 * 
	 * Plugin configuration page
	 * 
	 * @return page in html
	 */
	public static function getOptionsPage(){
?>
		<div class="wrap">
		<h2><?php _e('WP Vitrine Frugar', 'wp-vitrine-frugar');?></h2>
		
		<form method="post" action="options.php">
	    <?php
		    settings_fields( 'wp_vitrine_frugar_options' );
		    do_settings_sections( 'wp-vitrine-frugar' );
	    ?>
		    <p class="submit">
		    <input type="submit" class="button-primary" value="<?php _e('Save Changes', 'wp-vitrine-frugar') ?>" />
		    </p>
		
		</form>
		</div>
<?php
	}
	
	/**
	 * validates configuration options
	 * @param $input value to validate
	 * @return beautified input string or empty string, if input didnt passed validation
	 */
	public static function _validateOption($input){
		if($input['show_in_media_toolbar'] === '1'){
			$input['show_in_media_toolbar'] = true;
		}
		if($input['show_in_editor_toolbar'] === '1'){
			$input['show_in_editor_toolbar'] = true;
		}
		if($input['show_in_fullscreen'] === '1'){
			$input['show_in_fullscreen'] = true;
		}
		if($input['keep_settings'] === '1'){
			$input['keep_settings'] = true;
		}
		return $input;
	}
	
	/**
	 * 
	 *  Does some stuff before section output. not used for now
	 */
	public static function _getOptionsSection(){}
	
	/**
	 * 
	 * Display button option
	 */
	public static function _getShowOption(){
		$checkboxes = array('show_in_media_toolbar' => __('in Media toolbar', 'wp-vitrine-frugar'), 'show_in_editor_toolbar' => __('in Editor toolbar', 'wp-vitrine-frugar'), 'show_in_fullscreen' => __('in FullScreen Mode', 'wp-vitrine-frugar'));
		echo '<ul>';
		foreach ($checkboxes as $name => $title){
?>
			<li><input id="wp_vitrine_frugar_<?php echo $name;?>" type="checkbox" value="1" name="wp_vitrine_frugar[<?php echo $name;?>]" <?php checked(true, self::getInstance()->getOption($name));?> /> <label for="wp_vitrine_frugar_<?php echo $name;?>"><?php echo $title;?></label></li>
<?php
		}
		echo '</ul>';
	}
	
	/**
	 * 
	 * Display "keep settings" option
	 */
	public static function _getKeepOption(){
?>
		<input id="wp_vitrine_frugar_keep_settings" type="checkbox" value="1" name="wp_vitrine_frugar[keep_settings]" <?php checked(true, self::getInstance()->getOption('keep_settings'));?> /> <label for="wp_vitrine_frugar_keep_settings"><?php _e('Keep settings upon plugin deactivation. Otherwise they will be deleted', 'wp-vitrine-frugar');?></label>
<?php
	}
	
	/**
	 * include easy embed styles (for main window and for thickbox dialog)
	 */
	public static function _printStylesheet() {
		wp_enqueue_style('wpvitrinefrugar-buttons', plugins_url('css/style.css', __FILE__));
?><style>
span.mce_wpvitrinefrugar {
	background: url('<?php echo self::getConfig('button_url')?>') no-repeat center !important;
}
</style>
<?php
	}
	
	/**
	 * Checks $_REQUEST variable for supported data
	 * 
	 * @param string $type embed type
	 * @return array, containing supported values from request
	 */
	protected static function getEmbedFromRequest() {
		$args = $_REQUEST;
		$allowed = self::getConfig('supported_attributes');
		
		$provider = $args['provider'];
		if(in_array($provider, self::getConfig('supported_providers'))){
			$allowed = array_merge($allowed, array_keys(self::getConfig('supported_attributes_' . $provider)));
		}
		
		$all_keys = array_keys($args);
		foreach ($all_keys as $key){
			if(!in_array($key, $allowed) || $args[$key] === ''){
				unset($args[$key]);
			}
		}
		return $args;
	}
	/**
	 * Executes embed request and echoes html result
	 * used as dispatcher for ajax request
	 */
	public static function doEmbedPreview() {
		if($_REQUEST['url'] == ''){
			die;
		}

		$args = self::getEmbedFromRequest();
		$url = $args['url'];
		unset($args['url']);
		
		require_once( dirname(__FILE__) . '/inc/class-easyoembed.php' );
		die(VitrineOFrugar::getInstance()->get_html($url, $args));
	}
	
	/**
	 * 
	 * Prints icons url to make it available in javascript without hardcoding path
	 */
	public static function _printJSIconsURL() {
?><script type="text/javascript">
	var wpVitrineFrugarConfig = function(){
		return {
			buttonURL : '<?php echo self::getConfig('button_url')?>'
			, loadingIconURL : '<?php echo self::getConfig('loading_icon_url')?>'
			, providers : ['<?php echo implode("','", self::getConfig('supported_providers'))?>']
		};
	};
</script><?php
	}
	
	/**
	 * Return html for text input with name and value
	 * @param string $name name for input
	 * @param string $value value for input
	 * @return string html markup 
	 */
	protected static function getTextHtml($name, $value) {
		return '<input name="' . $name . '" value="' . $value . '" type="text" />';
	}
	
	/**
	 * Return html for checkbox with name and value
	 * @param string $name name for input
	 * @param boolean $value value for input
	 * @return string html markup 
	 */
	protected static function getCheckboxHtml($name, $value) {
		return '<input name="' . $name . '" value="1" type="checkbox" ' . checked(true, $value, false) . '/>';
	}
	
	/**
	 * Return html for select with name and value
	 * @param string $name name for input
	 * @param array $values possible values
	 * @param string $value current value
	 * @return string html markup 
	 */
	protected static function getSelectHtml($name, $values, $current) {
		if(!is_array($values)){
			return '';
		}
		$html = '<select name="' . $name . '">';
		foreach ($values as $value){
			$html .= '<option' . selected($value, $current, false) . '>' . $value . '</option>';
		}
		$html .= '</select>';
		return $html;
	}
	/**
	 *	Echoes additional form fields depending on provider
	 *	used as dispatcher for ajax request
	 */	

	public static function getAdditionalFieldsForProvider() {	
		
		
		
?>		
<?php
		foreach ($attributes as $name => $args) {
			
			
?>
		
<?php			
		}
		die;
	}
}
}//is_admin()
?>
