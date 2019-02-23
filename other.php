<?php

/**
 * Merchant ID       : 1000055
 * ApiKey                   : 8018D37C71411E48E0531415380A298D
 * Name                     : OB-Fit.com
 * Status                   : ACTIVE
 * Base URL             : https://sandbox.homecredit.co.id/online-api/
 *  
 */


"shippingFirstName" => $order->get_shipping_first_name(),
"shippingLastName" => $order->get_shipping_last_name(),
"shippingEmail" => $order->get_shipping_email(),
"shippingPhone" => $order->get_shipping_phone(),
"shippingAddress" => $order->get_shipping_address_1(),
"shippingCity" => $order->get_shipping_city(),
"shippingArea" => array(
    "title" => "JABODETABEK",
    "type" => "text",
    "default" => "JABODETABEK",
),
"shippingZipcode" => $order->get_shipping_postcode(),
"country_code" => (strlen($this->convert_country_code($order->get_shipping_country()) != 3) ? 'IDN' : $this->convert_country_code($order->get_billing_country())),



"totalAmount" =>$total_cost, //get total cost of order items // WC()->cart->get_cart_subtotal();
"description" => $this->get_option('homecredit_description'),
"callbackUrl" => WC()->api_request_url( 'WC_Homecredit_Payment_Gateway'), //register callback
"returnUrl" => $order->get_checkout_order_received_url(), //return to this page
"merchantBusinessLogoUrl" => $this->homecredit_merchant_logo, 
"merchantAccountNumber" => $this->homecredit_merchant_number,
"cancellationUrl" => get_home_url(), //checkout url
"clientReference" => date('s-').rand(0, 100).'-'.$order_id //generate a unique id the client reference