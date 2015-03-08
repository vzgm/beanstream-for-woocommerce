<?php
/**
 * Beanstream Gateway
 *
 * Provides a Beanstream Payment Gateway.
 *
 * @class       Beanstream_Gateway
 * @extends     WC_Payment_Gateway
 * @version     1.0
 * @package     WooCommerce/Classes/Payment
 * @author      Velmurugan Kuberan
 *
 * Thanks to both Stephen Zuniga & Sean Voss
 * Stephen Zuniga // http://stephenzuniga.com
 * Sean Voss // https://github.com/seanvoss/striper
 *
 */
 
 // Exit if accessed directly
 if ( ! defined( 'ABSPATH' ) ) exit;
 
 class Beanstream_Gateway extends WC_Payment_Gateway {
	protected $order                     = null;
    protected $form_data                 = null;
    protected $transaction_id            = null;
    protected $transaction_error_message = null;
	
	public function __construct() {
		global $beanstream_for_wc;
		
		//testing beanstream api, delete after testing all;
		$order_id = bin2hex(mcrypt_create_iv(22, MCRYPT_DEV_URANDOM));
		
		
		$post = array(
					'merchant_id' => $beanstream_for_wc->settings['merchant_id'],
					'order_number' => $order_id,
					'amount' => 10.00,
					'payment_method' => 'card',
					'card' => array(
						'name' => 'John Doe',
						'number' => '5100000010001004',
						'expiry_month' => '02',
						'expiry_year' => '17',
						'cvd' => '123'
					)
				);        

		Beanstream_API::post_data( $post, 'payments' );
		
        $this->id           = 'beanstream';
        $this->method_title = 'Beanstream for WooCommerce';
        $this->has_fields   = true;
		
	    // Init settings
        $this->init_form_fields();
        $this->init_settings();
					
        // Use settings
        $this->enabled     = $this->settings['enabled'];
        $this->title       = $this->settings['title'];
        $this->description = $this->settings['description'];	
		
		// Get current user information
        $this->beanstream_customer_info = get_user_meta( get_current_user_id(), $beanstream_for_wc->settings['beanstream_db_location'], true );
		
		// Add an icon with a filter for customization
        $icon_url = apply_filters( 'beanstream_icon_url', BEANSTREAM_URL_PATH . 'assets/images/credits.png' );
        if ( $icon_url ) {
            $this->icon = $icon_url;
        }
		
		 // Hooks
        add_action( 'woocommerce_update_options_payment_gateways', array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'woocommerce_credit_card_form_end', array( $this, 'after_cc_form' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts_styles' ) );
	}
	
	/**
     * Check if this gateway is enabled and all dependencies are fine.
     * Disable the plugin if dependencies fail.
     *
     * @access      public
     * @return      bool
     */
    public function is_available() {
        global $beanstream_for_wc;
				
        if ( $this->enabled === 'no' ) {
            return false;
        }
		
        // Stripe won't work without keys
        if ( ! $beanstream_for_wc->settings['merchant_id'] && ! $beanstream_for_wc->settings['api_pass_key'] ) {
            return false;
        }
		
		// Disable plugin if we don't use ssl
        if ( ! is_ssl() && $this->settings['testmode'] === 'no' ) {
            return false;
        }
		
        return true;
    }
	
	/**
     * Send notices to users if requirements fail, or for any other reason
     *
     * @access      public
     * @return      bool
     */
    public function admin_notices() {
        global $beanstream_for_wc, $pagenow, $wpdb;

        if ( $this->enabled == 'no') {
            return false;
        }

        // Check for API Keys
        if ( ! $beanstream_for_wc->settings['merchant_id'] && ! $beanstream_for_wc->settings['api_pass_key'] ) {
            echo '<div class="error"><p>' . __( 'Beanstream needs Merchand id & API pass Keys to work, please find your Merchand id and API pass Keys in the <a href="https://www.beanstream.com/admin/sDefault.asp" target="_blank">Beanstream accounts section</a>.', 'beanstream-for-woocommerce' ) . '</p></div>';
            return false;
        }

        // Force SSL on production
        if ( get_option( 'woocommerce_force_ssl_checkout' ) == 'no' ) {
            echo '<div class="error"><p>' . __( 'Beanstream needs SSL in order to be secure. Read more about forcing SSL on checkout in <a href="http://docs.woothemes.com/document/ssl-and-https/" target="_blank">the WooCommerce docs</a>.', 'beanstream-for-woocommerce' ) . '</p></div>';
            return false;
        }

    }
	
	/**
     * Initialise Gateway Settings Form Fields
     *
     * @access      public
     * @return      void
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'type'          => 'checkbox',
                'title'         => __( 'Enable/Disable', 'beanstream-for-woocommerce' ),
                'label'         => __( 'Enable Beanstream for WooCommerce', 'beanstream-for-woocommerce' ),
                'default'       => 'yes'
            ),
            'title' => array(
                'type'          => 'text',
                'title'         => __( 'Title', 'beanstream-for-woocommerce' ),
                'description'   => __( 'This controls the title which the user sees during checkout.', 'beanstream-for-woocommerce' ),
                'default'       => __( 'Credit Card Payment', 'beanstream-for-woocommerce' )
            ),
            'description' => array(
                'type'          => 'textarea',
                'title'         => __( 'Description', 'beanstream-for-woocommerce' ),
                'description'   => __( 'This controls the description which the user sees during checkout.', 'beanstream-for-woocommerce' ),
                'default'       => '',
            ),
			'testmode' => array(
                'type'          => 'checkbox',
                'title'         => __( 'Test Mode', 'beanstream-for-woocommerce' ),
                'description'   => __( 'Enable the test mode on Beanstream\'s account panel to verify everything works before going live.', 'beanstream-for-woocommerce' ),
                'label'         => __( 'Turn on testing', 'beanstream-for-woocommerce' ),
                'default'       => 'no'
            ),
            'charge_type' => array(
                'type'          => 'select',
                'title'         => __( 'Charge Type', 'beanstream-for-woocommerce' ),
                'description'   => __( 'Choose to capture payment at checkout, or authorize only to capture later.', 'beanstream-for-woocommerce' ),
                'options'       => array(
                    'capture'   => __( 'Authorize & Capture', 'beanstream-for-woocommerce' ),
                    'authorize' => __( 'Authorize Only', 'beanstream-for-woocommerce' )
                ),
                'default'       => 'capture'
            ),            
            'saved_cards' => array(
                'type'          => 'checkbox',
                'title'         => __( 'Saved Cards', 'beanstream-for-woocommerce' ),
                'description'   => __( 'Allow customers to use saved cards for future purchases.', 'beanstream-for-woocommerce' ),
                'default'       => 'yes',
            ),            
            'merchant_id'   => array(
                'type'          => 'text',
                'title'         => __( 'Beanstream Merchand id', 'beanstream-for-woocommerce' ),
                'default'       => '',
            ),
            'api_pass_key' => array(
                'type'          => 'text',
                'title'         => __( 'Beanstream API pass keys', 'beanstream-for-woocommerce' ),
                'default'       => '',
            ),
        );
    }
	
	 /**
     * Admin Panel Options
     * - Options for bits like 'title' and availability on a country-by-country basis
     *
     * @access      public
     * @return      void
     */
    public function admin_options() {

        $options_base = 'admin.php?page=wc-settings&tab=checkout&section=' . strtolower( get_class( $this ) );
        ?>
        <h3><?php echo ( ! empty( $this->method_title ) ) ? $this->method_title : __( 'Settings', 'woocommerce' ) ; ?></h3>
        <p><?php _e( 'Allows Credit Card payments through <a href=""https://www.beanstream.com/">Beanstream</a>. You can find your Merchand id and API Pass Keys in your <a href="https://www.beanstream.com/admin/sDefault.asp">Beanstream Account Settings</a>.', 'beanstream-for-woocommerce' ); ?></p>
        
        <table class="form-table">
        	<?php $this->generate_settings_html(); ?>
        </table>
        
        <?php
    }
	
	/**
     * Load dependent scripts & styles
     *
     * @access      public
     * @return      void
     */
    public function load_scripts_styles() {		
		wp_enqueue_style( 'beanstream-style', BEANSTREAM_URL_PATH . 'assets/css/beanstream-style.css' );
	}
	
	/**
     * Output payment fields, optional additional fields and woocommerce cc form
     *
     * @access      public
     * @return      void
     */
    public function payment_fields() {

        // Output the saved card data
        //gateway_get_template( 'payment-fields.php' ); //implement this part of code later
		
		// Output WooCommerce 2.1+ cc form
        $this->credit_card_form( array(
            'fields_have_names' => true,
        ) );

    }
	
	/**
     * Add additional fields just below the credit card form
     *
     * @access      public
     * @param       string $gateway_id
     * @return      void
     */
	 
	public function after_cc_form( $gateway_id ) {
		global $beanstream_for_wc;
		
		// Ensure that we're only outputting this for the s4wc gateway
        if ( $gateway_id === $this->id && $beanstream_for_wc->settings['saved_cards'] == 'yes' ) {		
            
			woocommerce_form_field( 'beanstream-user-savecard', array(				                
				'type'				=> 'checkbox',
				'label'             => __( 'Save Card', 'beanstream-for-woocommerce' ),
				'class'             => array( 'form-row-first form-row-first-padding' ),	
				'input_class'       => array( 'beanstream-save-card' ),			
                'required'          => false,
				'value'				=> false,
            ) );
			
			woocommerce_form_field( 'beanstream-user-cvc', array(				                
				'type'				=> 'checkbox',
				'label'             => __( 'Save CVC', 'beanstream-for-woocommerce' ),
				'class'             => array( 'form-row-first form-row-first-padding' ),	
				'input_class'       => array( 'beanstream-save-card' ),			
                'required'          => false,
				'value'				=> false,
            ) );

			
		}
	}
	
	/**
     * Validate credit card form fields
     *
     * @access      public
     * @return      void
     */
    public function validate_fields() {
				
        $form = array(
            'card-number'   => isset( $_POST['beanstream-card-number'] ) ? $_POST['beanstream-card-number'] : '',
            'card-expiry'   => isset( $_POST['beanstream-card-expiry'] ) ? $_POST['beanstream-card-expiry'] : '',
            'card-cvc'      => isset( $_POST['beanstream-card-cvc'] ) ? $_POST['beanstream-card-cvc'] : '',
        );

        if ( $form['card-number'] == '' ) {
            $field = __( 'Credit Card Number', 'beanstream-for-woocommerce' );
            wc_add_notice( $this->get_form_error_message( $field, $form['card-number'] ), 'error' );
        }
        if ( $form['card-expiry'] == '' ) {
            $field = __( 'Credit Card Expiration', 'beanstream-for-woocommerce' );
            wc_add_notice( $this->get_form_error_message( $field, $form['card-expiry'] ), 'error' );
        }
        if ( $form['card-cvc'] == '' ) {
            $field = __( 'Credit Card CVC', 'beanstream-for-woocommerce' );
            wc_add_notice( $this->get_form_error_message( $field, $form['card-cvc'] ), 'error' );
        }
    }
	
	/**
     * Get error message for form validator given field name and type of error
     *
     * @access      protected
     * @param       string $field
     * @param       string $type
     * @return      string
     */
    protected function get_form_error_message( $field, $type = 'undefined' ) {

        if ( $type === 'invalid' ) {
            return sprintf( __( 'Please enter a valid %s.', 'beanstream-for-woocommerce' ), "<strong>$field</strong>" );
        } else {
            return sprintf( __( '%s is a required field.', 'beanstream-for-woocommerce' ), "<strong>$field</strong>" );
        }
    }
			
 }