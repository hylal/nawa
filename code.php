<?php
/*
Plugin Name:  Code HCi
Plugin URI:   https://github.com/hylal/wannami
Description:  Untuk pembayaran menggunakan home credit indonesia
Version:      0.01
Author:       Hilaludin Wahid
Author URI:   https://wahid.biz
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
*/

/*
Home Credit OB Fit is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

Home Credit OB Fit is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Home Credit OB Fit. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
*/



if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))){
    echo "<div class='error notice'><p>Woocommerce has to be installed and active to use the the Hubtel Payments Gateway</b> plugin</p></div>";
    return;
}

function homecredit_init()
{


	function add_homecredit_gateway_class( $methods ) 
	{
		$methods[] = 'WC_Homecredit_Payment_Gateway'; 
		return $methods;
	}
	add_filter( 'woocommerce_payment_gateways', 'add_homecredit_gateway_class' );

	if(class_exists('WC_Payment_Gateway'))
	{
		class WC_Homecredit_Payment_Gateway extends WC_Payment_Gateway 
		{

			public function __construct()
			{

				$this->id               = 'homecredit-payments';
				$this->icon             = plugins_url( 'images/hcid_logo.jpg' , __FILE__ ) ;
				$this->has_fields       = true;
				$this->method_title     = 'Homecredit Payments'; 
				$this->description       = $this->get_option( 'homecredit_description');            
				$this->init_form_fields();
				$this->init_settings();

				$this->title                    = $this->get_option( 'homecredit_title' );
				$this->homecredit_description       = $this->get_option( 'homecredit_description');
				$this->homecredit_clientid  	    = $this->get_option( 'homecredit_clientid' );
				$this->homecredit_clientsecret      = $this->get_option( 'homecredit_clientsecret' );
				$this->homecredit_merchant_number   = $this->get_option( 'homecredit_merchant_number' );
				$this->homecredit_merchant_logo     = $this->get_option( 'homecredit_merchant_logo' );

				
				if (is_admin()) 
				{

					if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
						add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(&$this, 'process_admin_options'));
					} else {
						add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
					}				}
				
				//register webhook listener action
				add_action( 'woocommerce_api_wc_homecredit_payment_gateway', array( $this, 'check_homecredit_payment_webhook' ) );

			}

			public function init_form_fields()
			{

				$this->form_fields = array(
					'enabled' => array(
						'title' =>  'Enable/Disable',
						'type' => 'checkbox',
						'label' =>  'Enable Homecredit Payments',
						'default' => 'yes'
						),

					'homecredit_title' => array(
						'title' =>  'Title',
						'type' => 'text',
						'description' =>  'This displays the title which the user sees during checkout options.',
						'default' =>  'Pay With Homecredit',
						'desc_tip'      => true,
						),

					'homecredit_description' => array(
						'title' =>  'Description',
						'type' => 'textarea',
						'description' =>  'This is the description which the user sees during checkout.',
						'default' =>  'Safe and secure payments with Ghanaian issued cards and mobile money from all networks.',
						'desc_tip'      => true,
						),

					'homecredit_clientid' => array(
						'title' =>  'Client ID',
						'type' => 'text',
						'description' =>  'This is your homecredit API Client ID which you can find in your Dashboard.',
						'default' => '',
						'desc_tip'      => true,
						'placeholder' => 'Homecredit API Clientid'
						),

					'homecredit_clientsecret' => array(
						'title' =>  'Client Secret',
						'type' => 'text',
						'description' =>  'This is your homecredit API Client Secret which you can find in your Dashboard.',
						'default' => '',
						'desc_tip'      => true,
						'placeholder' => 'homecredit API Clientsecret'
						),

				'homecredit_merchant_number' => array(
						'title' =>  'homecredit Merchant Number',
						'type' => 'text',
						'description' =>  'This is your homecredit Merchant Account which you can find in your Hubtel Dashboard',
						'default' => '',
						'desc_tip'      => true,
						'placeholder' => 'homecredit Merchant Account Number'
						),
			'homecredit_merchant_logo' => array(
						'title' =>  'Homecredit Merchant Logo URL',
						'type' => 'text',
						'description' =>  'This is the Merchant logo URL that should be displayed on the checkout page.',
						'default' => '',
						'desc_tip'      => true,
						'placeholder' => 'http://merchant-website.com/logo.png'
						)						
					);

			}

			/**
			 * handle webhook callback
			 */
			public function check_homecredit_payment_webhook()
			{
				// receive callback 
				$decode_webhook = json_decode(@file_get_contents("php://input"));

				global $woocommerce;
				$order_ref = $decode_webhook->Data->ClientReference;

				//retrieve order id from the client reference
				$order_ref_items = explode('-', $order_ref);
				$order_id = $order_ref_items[2];

				$order = new WC_Order( $order_id );
				//process the order with returned data from Home Credit callback
				if($decode_webhook->ResponseCode == '0000' && $decode_webhook->Status == 'Success')
				{
					
					$order->add_order_note('Homecredit payment completed');				
					
					//Update the order status
					$order->update_status('payment processed', 'Payment Successful with Homecredit');
					$order->payment_complete();

					//reduce the stock level of items ordered
					wc_reduce_stock_levels($order_id);
				}else{
					//add notice to order to inform merchant of 
					$order->add_order_note('Payment failed at Homecredit. Send an email to info@ob-fit.com for assistance using this checkout ID:'
											.$decode_webhook->Data->CheckoutId);
				}
				
			}

			/**
			 * process payments
			 */
			public function process_payment($order_id)
			{
				global $woocommerce;

				$order = new WC_Order( $order_id );

				// Get an instance of the WC_Order object
				$order = wc_get_order( $order_id );

				$order_data = $order->get_items();

				//build order items for the homecredit request body
				$homecredit_items = [];
				$items_counter = 0;
				$total_cost = 0;
				foreach ($order_data as $order_key => $order_value):

					$homecredit_items[$items_counter] = [
							"name" => $order_value->get_name(),
							"quantity" => $order_value->get_quantity(), // Get the item quantity
							"unitPrice" => $order_value->get_total()/$order_value->get_quantity()
					];
					
						$total_cost += $order_value->get_total();
						$items_counter++;
				endforeach;


				//homecredit payment request body args
				$homecredit_request_args = [
					"items" => $homecredit_items,
					"totalAmount" =>$total_cost, //get total cost of order items // WC()->cart->get_cart_subtotal();
					"description" => $this->get_option('homecredit_description'),
					"callbackUrl" => WC()->api_request_url( 'WC_Homecredit_Payment_Gateway'), //register callback
					"returnUrl" => $order->get_checkout_order_received_url(), //return to this page
					"merchantBusinessLogoUrl" => $this->homecredit_merchant_logo, 
					"merchantAccountNumber" => $this->homecredit_merchant_number,
					"cancellationUrl" => get_home_url(), //checkout url
					"clientReference" => date('s-').rand(0, 100).'-'.$order_id //generate a unique id the client reference
				];
				
				
				//initiate request to Homecredit payments API
				$base_url = 'https://sandbox.homecredit.co.id/online-api/';
				$response = wp_remote_post($base_url, array(
					'method' => 'POST',
					'timeout' => 45,
					'headers' => array(
						'Authorization' => 'Basic '.base64_encode($this->homecredit_clientid.':'.$this-homecredit_clientsecret),
						'Content-Type' => 'application/json'
					),
					'body' => json_encode($homecredit_request_args)
					)
				);

                var_dump($response);
                die;

				//retrieve response body and extract the 
				$response_code = wp_remote_retrieve_response_code( $response );
				$response_body = wp_remote_retrieve_body($response);

				$response_body_args = json_decode($response_body, true);

				switch ($response_code) {
					case 200:
							
							$order->update_status('on-hold: awaiting payment', 'Awaiting payment');
							
							// Remove cart
							$woocommerce->cart->empty_cart();

							return array(
								'result'   => 'success',
								'redirect' => $response_body_args['data']['checkoutUrl']
							);
						break;

					case 400:
							wc_add_notice('Payment Request Error: '.ucwords($response_body_args['data']), 'error' );
						break;

					case 500:
							wc_add_notice('Payment System Error: Contact Homecredit for assistance', 'error' );
						break;

					default:
						
							wc_add_notice('Payment Error: Could not reach Homecredit Payment Gateway. Please try again', 'error' );

						break;
				}
			
					
			}

        }  // end of class WC_Homecredit_Payment_Gateway

} // end of if class exist WC_Gateway

}

/*Activation hook*/
add_action( 'plugins_loaded', 'homecredit_init' );


