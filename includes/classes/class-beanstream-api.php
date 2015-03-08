<?php
/**
 * Functions for interfacing with Beanstream API
 *
 * @class       Beanstream_API
 * @version     1.0
 * @author      Velmurugan Kuberan
 */
 
 if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
 
 class Beanstream_API {
	 
	/**
	 * Endpoints: Set api_endpoint URL with inline {0} & {1} for  platform & api version variable's respectively
	 */
	public static $api_endpoint = 'https://{0}.beanstream.com/api/{1}/';

    /**
     * Post data to Beanstream's server by passing data and an API endpoint
     *
     * @access      public
     * @param       array $post_data
     * @param       string $post_location
     * @return      array
     */
    public static function post_data( $post_data, $post_location = 'payments' ) {
        
		global $beanstream_for_wc;
		$base_url = str_replace("{0}", $beanstream_for_wc->settings['platform'], self::$api_endpoint );
		$payment_endpoint = str_replace("{1}", $beanstream_for_wc->settings['api_version'], $base_url );
		
		$merchantId = $beanstream_for_wc->settings['merchant_id'];
		$passcode 	= $beanstream_for_wc->settings['api_pass_key'];
		
		$response = wp_remote_post( $payment_endpoint . $post_location, array(
            'method'        => 'POST',
            'headers'       => array(
				'Content-Type'	=> 'application/json',
                'Authorization' => 'Passcode ' . base64_encode( $merchantId . ":" . $passcode ),
            ),
            'body'          => json_encode($post_data),
            'timeout'       => 70,
            'sslverify'     => false,
            'user-agent'    => 'WooCommerce-Beanstream',
        ) );
				
        return Beanstream_API::parse_response( $response );
    }

	/**
     * Get data from Beanstream's server by passing an API endpoint
     *
     * @access      public
     * @param       string $get_location
     * @return      array
     */
    public static function get_data( $get_location ) {
		
        global $beanstream_for_wc;
		$base_url = str_replace("{0}", $beanstream_for_wc->settings['platform'], self::$api_endpoint );
		$payment_endpoint = str_replace("{1}", $beanstream_for_wc->settings['api_version'], $base_url );
		
		$merchantId = $beanstream_for_wc->settings['merchant_id'];
		$passcode 	= $beanstream_for_wc->settings['api_pass_key'];

        $response = wp_remote_get( self::$api_endpoint . $get_location, array(
            'method'        => 'GET',
            'headers'       => array(
                'Authorization' => 'Basic ' . base64_encode( $s4wc->settings['secret_key'] . ':' ),
            ),
            'timeout'       => 70,
            'sslverify'     => false,
            'user-agent'    => 'WooCommerce-Beanstream',
        ) );

        //return S4WC_API::parse_response( $response );
    }	
	
	/**
     * Parse Beanstream's response after interacting with the API
     *
     * @access      public
     * @param       array $response
     * @return      array
     */
    public static function parse_response( $response ) {
        if ( is_wp_error( $response ) ) {
            throw new Exception( 'beanstream_problem_connecting' );
        }

        if ( empty( $response['body'] ) ) {
            throw new Exception( 'beanstream_empty_response' );
        }

        $parsed_res = json_decode( $response['body'] );
		
		print '<pre>';
		print_r( $parsed_res );
		print '</pre>';
		
        // Handle response
        if( isset( $parsed_res->code ) && 1 < $parsed_res->code && !( $parsed_res->http_code >= 200 && $parsed_res->http_code < 300 ) ) {
            throw new Exception( $parsed_res->message );
        }

        return $parsed_res;
    }
 }