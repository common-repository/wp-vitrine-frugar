<?php

require_once( ABSPATH . WPINC . '/class-oembed.php' );
/**
 * 
 * Extending original class to allow inclusion of additional params
 * @author ibarmin
 *
 */
class VitrineOFrugar extends WP_oEmbed {
	protected static $instance = null;
	//for inheritance compliance, sadly
	//private function __construct() {}
	private function __clone() {}
 	public static function getInstance() {
        if ( is_null(self::$instance) ) {
            self::$instance = new self;
        }
        return self::$instance;
    }
	
    /**
     * Connects to a oEmbed provider and returns the result (supports additional params) 
     * @see WP_oEmbed::fetch()
	 * @param string $provider The URL to the oEmbed provider.
	 * @param string $url The URL to the content that is desired to be embedded.
	 * @param array $args Optional arguments. Usually passed from a shortcode.
	 * @return bool|object False on failure, otherwise the result in the form of an object.
     */
    function fetch( $provider, $url, $args = '' ) {
		$provider = add_query_arg( 'maxwidth', (int) $args['maxwidth'], $provider );
		$provider = add_query_arg( 'maxheight', (int) $args['maxheight'], $provider );
		unset($args['maxwidth'], $args['maxheight']);
		foreach ($args as $name => $value) {
			if($value === '' || $name == '')continue;
			$provider = add_query_arg( $name, $value, $provider );
		}
		$provider = add_query_arg( 'url', urlencode($url), $provider );
	
		foreach( array( 'json', 'xml' ) as $format ) {
			$result = $this->_fetch_with_format( $provider, $format );
			if ( is_wp_error( $result ) && 'not-implemented' == $result->get_error_code() )
				continue;
			return ( $result && ! is_wp_error( $result ) ) ? $result : false;
		}
		return false;
	}
	
}
?>