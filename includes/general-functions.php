<?php
/**
 * add commonly used functions in this file.
 */
 
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit; 

/**
 * Wrapper of wc_get_template to relate directly to this gateway
 *
 * @param       string $template_name
 * @param       array $args
 * @return      string
 */
function gateway_get_template( $template_name, $args = array() ) {
    $template_path = WC()->template_path() . '/beanstream/';	
    $default_path 	= BEANSTREAM_DIR_PATH . '/templates/';
    return wc_get_template( $template_name, $args, $template_path, $default_path );
}



?>