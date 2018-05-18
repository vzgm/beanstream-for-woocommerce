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
        $this->id           = 'beanstream';
        $this->method_title = 'Beanstream for WooCommerce';
        $this->has_fields   = true;
		$this->supports     = array(
            'default_credit_card_form',
            'products',
            'refunds'
        );
		
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
		add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts_styles' ) );
		add_action( 'woocommerce_credit_card_form_start', array( $this, 'before_cc_form' ) );
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
            'order_prefix' => array(
                'type'          => 'text',
                'title'         => __( 'Order prefix', 'beanstream-for-woocommerce' ),
                'description'   => __( 'Order prefix is optional but if added here, wil be added to order name: PREFIX_xxxxxxx', 'beanstream-for-woocommerce' ),
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
     * Add additional fields just above the credit card form
     *
     * @access      public
     * @param       string $gateway_id
     * @return      void
     */
    public function before_cc_form( $gateway_id ) {
        global $beanstream_for_wc;
		
		// Ensure that we're only outputting this for the s4wc gateway
        if ( $gateway_id === $this->id && $beanstream_for_wc->settings['testmode'] == 'yes' ) {
			echo 'Test mode enabled';
		}
	}
	
	/**
     * Output payment fields, optional additional fields and woocommerce cc form
     *
     * @access      public
     * @return      void
     */
    public function payment_fields() {

		// Output WooCommerce 2.1+ cc form
        $this->credit_card_form( array(
            'fields_have_names' => true,
        ) );

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
	
    /**
     * Process the payment and return the result
     *
     * @access      public
     * @param       int $order_id
     * @return      array
     */
    public function process_payment( $order_id ) {

        if ( $this->send_to_beanstream( $order_id ) ) {
			
            $this->order_complete();

            $result = array(
                'result' => 'success',
                'redirect' => $this->get_return_url( $this->order )
            );

            return $result;
        } else {
            $this->payment_failed();
			
            // Add a generic error message if we don't currently have any others
            if ( wc_notice_count( 'error' ) == 0 ) {
                wc_add_notice( __( 'Transaction Error: Could not complete your payment.', 'beanstream-for-woocommerce' ), 'error' );
            }
        }
    }
	
	/**
     * Process refund
     *
     * Overriding refund method
     *
     * @access      public
     * @param       int $order_id
     * @param       float $amount
     * @param       string $reason
     * @return      mixed True or False based on success, or WP_Error
     */
    public function process_refund( $order_id, $amount = null, $reason = '' ) {
		global $beanstream_for_wc;
		$this->order = new WC_Order( $order_id );
		$this->transaction_id = $this->order->get_transaction_id();
		
		if ( ! $this->transaction_id ) {
            return new WP_Error( 'beanstream_refund_error',
                sprintf(
                    __( '%s Credit Card Refund failed because the Transaction ID is missing.', 'beanstream-for-woocommerce' ),
                    get_class( $this )
                )
            );
        }
		
		try {

            $refund_data = array();
			$refund_data['order_id'] 		= $order_id;
			$refund_data['transaction_id'] 	= $this->transaction_id;

            // If the amount is set, refund that amount, otherwise the entire amount is refunded
            if ( $amount ) {
                $refund_data['amount'] = $amount;
            }

            // If a reason is provided, add it to the Stripe metadata for the refund
            if ( $reason ) {
                $refund_data['metadata']['reason'] = $reason;
            }

            // Send the refund to the Beanstream API
            return Beanstream_API::create_refund( $refund_data );

        } catch ( Exception $e ) {
            $this->transaction_error_message = $beanstream_for_wc->get_error_message( $e );

            $this->order->add_order_note(
                sprintf(
                    __( '%s Credit Card Refund Failed with message: "%s"', 'beanstream-for-woocommerce' ),
                    get_class( $this ),
                    $this->transaction_error_message
                )
            );

            // Something failed somewhere, send a message.
            return new WP_Error( 'beanstream_refund_error', $this->transaction_error_message );
        }

        return false;
	}
	
	/**
     * Send form data to Beanstream
     * Handles sending the charge to an existing customer, a new customer (that's logged in), or a guest
     *
     * @access      protected
     * @param       int $order_id
     * @return      bool
     */
    protected function send_to_beanstream( $order_id ) {
		global $beanstream_for_wc;
		
		// Get the order based on order_id
    	$this->order = new WC_Order( $order_id );
		
		// Get the credit card details submitted by the form
        $this->form_data = $this->get_form_data();
			
        // Allow for any type of charge to use the same try/catch config
        $retcode = $this->charge_set_up();
			
		if( $retcode == false ) //if false then something went wrong.
			return false;
			
		// Save data for the "Capture"
        update_post_meta( $this->order->id, '_beanstream_capture', strcmp( $this->settings['charge_type'], 'authorize' ) == 0 );			
		return true;			
	}
	
	/**
     * Retrieve the form fields
     *
     * @access      protected
     * @return      mixed
     */
    protected function get_form_data() {

        if ( $this->order && $this->order != null ) {
            return array(
                'amount'        => (float) $this->order->get_total(),
                'customer'      => array(
                    'name'              => $this->order->billing_first_name . ' ' . $this->order->billing_last_name,
                    'billing_email'     => $this->order->billing_email,
                )
            );
        }

        return false;
    }
	
	/**
     * Set up the charge that will be sent to Beanstream
     *
     * @access      private
     * @return      void
     */
    private function charge_set_up() {
        global $beanstream_for_wc;

        try {
			//$order_id = bin2hex(mcrypt_create_iv(22, MCRYPT_DEV_URANDOM));
			$chargetype   = ( $this->settings['charge_type'] == 'capture' ) ? TRUE : FALSE;
			$cardnumber   = isset( $_POST['beanstream-card-number'] ) ? $_POST['beanstream-card-number'] : '';
        	$cardexpiry   = isset( $_POST['beanstream-card-expiry'] ) ? $_POST['beanstream-card-expiry'] : '';
        	$cardcvc      = isset( $_POST['beanstream-card-cvc'] ) ? $_POST['beanstream-card-cvc'] : '';
            $prefix       = ( isset($this->settings['order_prefix']) && !empty($this->settings['order_prefix']) ) ? $this->settings['order_prefix'].'_' : '';
			
			//split expiry field in order to get month and year section
			$cardexpiry	  = explode( "/", $cardexpiry);		
			$month		  = isset( $cardexpiry[0] ) ?  $cardexpiry[0] : '00';
			$year		  = isset( $cardexpiry[1] ) ?  $cardexpiry[1] : '00';
								
			$post 		  = array(
								'order_number' => $prefix.$this->order->id,
								'amount' => $this->form_data['amount'],
								'payment_method' => 'card',
								'card' => array(
									'name' => $this->form_data['customer']['name'],
									'number' => $cardnumber,
									'expiry_month' => trim($month),
									'expiry_year' => trim($year),
									'cvd' => $cardcvc,
									'complete' => $chargetype,
								)
							);
					
			$response = Beanstream_API::post_data( $post, 'payments' );	        	
			$this->charge = $response;
	        $this->transaction_id = $response->id;		
			return true;				
		} catch ( Exception $e ) {
			// Stop page reload if we have errors to show
   	        unset( WC()->session->reload_checkout );			
			$this->transaction_error_message = $beanstream_for_wc->get_error_message( $e );		
			wc_add_notice( __( 'Error:', 'beanstream-for-woocommerce' ) . ' ' . $beanstream_for_wc->get_error_message( $e ), 'error' );
			return false;
		}		
    }
		
	/**
     * Get the description of the Order
     *
     * @access      protected
     * @param       string $type Type of product being bought
     * @return      string
     */
    protected function get_charge_description( ) {
        $order_items = $this->order->get_items();
		$product_name = __( 'Purchases', 'beanstream-for-woocommerce' );

        // Grab first viable product name and use it
        foreach ( $order_items as $key => $item ) {
        	$product_name = $item['name'];
            break;
        }

        // Charge description
        $charge_description = sprintf(
            __( 'Payment for %s (Order: %s)', 'beanstream-for-woocommerce' ),
            $product_name,
            $this->order->get_order_number()
        );

        return apply_filters( 'beanstream_charge_description', $charge_description, $this->form_data, $this->order );
    }
	
	/**
     * Mark the payment as failed in the order notes
     *
     * @access      protected
     * @return      void
     */
    protected function payment_failed() {
        $this->order->add_order_note(
            sprintf(
                __( '%s payment failed with message: "%s"', 'beanstream-for-woocommerce' ),
                get_class( $this ),
                $this->transaction_error_message
            )
        );
    }
	
	/**
     * Mark the payment as completed in the order notes
     *
     * @access      protected
     * @return      void
     */
    protected function order_complete() {

        if ( $this->order->status == 'completed' ) {
            return;
        }

        $this->order->payment_complete( $this->transaction_id );

        $this->order->add_order_note(
            sprintf(
                __( '%s payment completed with Transaction Id of "%s"', 'beanstream-for-woocommerce' ),
                get_class( $this ),
                $this->transaction_id
            )
        );
    }
	
}