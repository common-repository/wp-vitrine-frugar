var wpVitrineFrugar = (function(){
	var main = window.dialogArguments || opener || parent || top;
	var jQuery = main.jQuery;
	var URLValue = null;
	var tabs = ['video', 'image', 'link', 'rich'];
	var root = null;
	var loadingHTML = '<div align="center"><img src="' + main.wpVitrineFrugarConfig().loadingIconURL + '" /></div>';
	var timerURLChange = null;
	var currentProvider = null;
	/**
	 * closing popup window
	 */
	function close() {
		main.tb_remove();
	}
	/**
	 * checking if input string is url
	 * dont need too much checks, just eliminate few empty runs of checkURLProviders
	 * @param string str input string
	 * @returns boolean check result
	 */
	function isURL(str){
		if(str.indexOf('http://') != 0 && str.indexOf('https://') != 0){
			return false;
		}
		return true;
	}
	/**
	 * event handler (both keyup and mouseup binded)
	 */
	function onURLChange (){
		if(timerURLChange !== null){
			clearTimeout(timerURLChange);
			timerURLChange = null;
		}
		if(this.value == '' || this.value == URLValue || !isURL(this.value)){
			return;
		}
		URLValue = this.value;
		timerURLChange = setTimeout(function(){
			wpVitrineFrugar.checkURLProviders(URLValue);
		}, 300);
	}
	/**
	 * fetches host from (valid) url string 
	 */
	function getHostFromURL(url){
		var pattern = /https?:\/\/([^\/]+)\/.*/i;
		if(pattern.test(url)){
			var matches = pattern.exec(url);
			return matches[1];
		} else {
			return false;
		}
	}
	return {
		/**
		 * imports all parent's stylesheets 
		 */
		preInit : function(){
			var styles = main.document.styleSheets;

			for ( var i = 0; i < styles.length; i++ ) {
				var media = styles.item(i).media.length > 0 ? styles.item(i).media.item(0) : 'all';
				document.write( '<link rel="stylesheet" href="' + styles.item(i).href + '" type="text/css" media="' + media + '" />' );
			}
		}
		/**
		 * localize text in popup window and binds events on url input field
		 */
		, init : function(){
			root = window.document.body;
			var h = root.innerHTML;
			root.innerHTML = main.tinymce.EditorManager.activeEditor.translate(h);
			jQuery(root).show();
			jQuery('input[name=url]', root).keyup(onURLChange).mouseup(onURLChange).change(onURLChange);
		}
		/**
		 * performs checks on form validity
		 * @param Form form to check
		 * @returns valid values array or boolean false if invalid
		 */
		, checkForm : function(form){
			if(form.url.value === ''){
				alert(form.url.title);
				jQuery(form.url).focus();
				return false;
			}
			values = [];
			jQuery('input,select,textarea', form).each(function(){
				var value = (this.type == "checkbox" ? (this.checked ? '1' : '0') : this.value);
				values.push({
					name: this.name
					, value: value
				});
			});
			return values;
		}
		/**
		 * submits the form and inserts embed shortcode into main editor
		 * @param Form form to submit
		 * @returns boolean false to stop submit event propagation
		 */
		, submit : function(form){
			var values = this.checkForm(form);
			if(values === false){
				return false;
			}

			var content = ['[embed'];
			jQuery(values).each(function(){
				//skip service fields and empty ones
				if(this.name == "url" || this.name == "type" || this.name == '' || this.value == ''){
					return;
				}
				content.push(this.name + '="' + this.value + '"');
			});
			
			content = content.join(' ') + ']' + form.url.value + '[/embed]\n';
			
			var editor = main.tinymce.EditorManager.activeEditor;
			editor.execCommand("mceBeginUndoLevel");
			editor.execCommand("mceInsertContent", false, content);
			editor.execCommand("mceEndUndoLevel");
			editor.execCommand('mceRepaint');

			if (main.tinymce.isOpera){
				main.setTimeout(close, 0);
			} else {
				close();
			}
			return false;
		}
		/**
		 * handles preview functionality
		 */
		, preview : function(){
			var values = this.checkForm(document.forms[0]);
			if(values === false){
				return false;
			}
			jQuery('#preview_area', root).html(loadingHTML);
			jQuery('#preview_row', root).show();

			var params = {
				action : 'wp_vitrine_frugar_preview'
			};
			jQuery(values).each(function(){
				params[this.name] = this.value;
			});
			jQuery.post(main.ajaxurl, params, function(html){
				jQuery('#hidepreview,#showpreview', root).toggle();
				jQuery('#preview_area', root).html(html);
			}, "html");
			
			return false;
		}
		/**
		 * hides preview area
		 */
		, hidePreview : function(){
			jQuery('#hidepreview,#showpreview', root).toggle();
			jQuery('#preview_row', root).hide();
		}
		/**
		 * adds additional fields to form based on url provider (only vimeo supported in 1.1)
		 */
		, checkURLProviders : function(url) {
			var providers = main.wpVitrineFrugarConfig().providers;
			var host = getHostFromURL(url);
			for(var i = 0; i < providers.length; i++){
				if(host.indexOf(providers[i]) !== -1){
					if(providers[i] === currentProvider) {
						return;
					}
					jQuery('#additional_button', root).show();
					jQuery('.additional_field', root).remove();
					currentProvider = providers[i];
					return;
				}
			}
			jQuery('#additional_button', root).hide();
		}
		, fetchAdditionalFields : function(){
			jQuery.post(main.ajaxurl, {
				action : 'wp_vitrine_frugar_additional_fields'
				, provider : currentProvider
			}, function(html){
				jQuery('#additional_button', root).after(html);
				jQuery('#additional_button', root).hide();
			}, "html");
		}
	};
})();

window.onload = wpVitrineFrugar.init;
wpVitrineFrugar.preInit();