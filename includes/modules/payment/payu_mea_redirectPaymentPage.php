<?php

require_once(dirname(__FILE__)."/../../library.payu/classes/class.PayuRedirectPaymentPage.php");		

class payu_mea_redirectPaymentPage {

	var $code, $title, $description, $enabled;

	// class constructor	
    function payu_mea_redirectPaymentPage() {
		global $order;
	  
		$this->signature = 'payu|payu_mea_redirectPaymentPage|1.0';

		$this->code = 'payu_mea_redirectPaymentPage';
		$this->title = MODULE_PAYMENT_PAYU_MEA_REDIRECTPAYMENTPAGE_TEXT_TITLE;
		$this->public_title = MODULE_PAYMENT_PAYU_MEA_REDIRECTPAYMENTPAGE_TITLE;
		$this->description = MODULE_PAYMENT_PAYU_MEA_REDIRECTPAYMENTPAGE_TEXT_DESCRIPTION;
		$this->sort_order = MODULE_PAYMENT_PAYU_MEA_REDIRECTPAYMENTPAGE_SORT_ORDER;		
		$this->enabled = ((MODULE_PAYMENT_PAYU_MEA_REDIRECTPAYMENTPAGE_STATUS == 'True') ? true : false);

		$this->status_text_before_redirect = 'Pending';
		$this->status_text_successful = 'Processing';

		if ((int)MODULE_PAYMENT_PAYU_MEA_REDIRECTPAYMENTPAGE_ORDER_STATUS_ID > 0) {
			$this->order_status = MODULE_PAYMENT_PAYU_MEA_REDIRECTPAYMENTPAGE_ORDER_STATUS_ID;
		}
		if (is_object($order)) {
			$this->update_status();
		}	  
		$this->form_action_url = tep_href_link('ext/modules/payment/payuMeaRedirectPaymentPage/redirect.php', '', 'SSL', false, false); 		
	}

	// class methods
    function update_status() {
		global $order;     
		return $order;
    }	
	
    function javascript_validation() {
		return false;
    }

    function selection() {	
		return array(
			'id' => $this->code,
            'module' => $this->public_title
		);	  
		return false;
    }

    function pre_confirmation_check() {
	   return false;
    }
	
	
	function confirmation()
    {        
    	// Variable initialization
        global $cartID, $cart_PayU_ID, $customer_id,
			$languages_id, $order, $order_total_modules;

		//ob_start();	
		//session_start();  

		$_SESSION['ss_order']='';
		$_SESSION['ss_order']['info']=$order->info;
		$_SESSION['ss_order']['totals']=$order->totals;
		$_SESSION['ss_order']['products']=$order->products;
		$_SESSION['ss_order']['customer']=$order->customer;
		$_SESSION['ss_order']['delivery']=$order->delivery;
		$_SESSION['ss_order']['content_type']=$order->content_type;
		$_SESSION['ss_order']['billing']=$order->billing;			 
		$_SESSION['ss_order_total_modules']='';		 

		if( tep_session_is_registered('cartID') )
        {
            $insert_order = false;

            if( tep_session_is_registered('cart_PayU_ID') )
            {
                $order_id = substr( $cart_PayU_ID,
					strpos($cart_PayU_ID, '-') + 1 );

                $curr_check = tep_db_query(
					"SELECT `currency` FROM ". TABLE_ORDERS ."
                    WHERE `orders_id` = '". (int)$order_id . "'" );
                $curr = tep_db_fetch_array( $curr_check );

                if( ($curr['currency'] != $order->info['currency']) ||
					($cartID != substr($cart_PayU_ID, 0, strlen($cartID))) )
                {
                    $check_query = tep_db_query(
						"SELECT `orders_id` FROM ". TABLE_ORDERS_STATUS_HISTORY ."
						WHERE `orders_id` = '". (int)$order_id ."' limit 1" );

                    if( tep_db_num_rows($check_query) < 1 )
		            {
		                tep_db_query(
							"DELETE FROM `". TABLE_ORDERS ."` WHERE `orders_id` = '". (int)$order_id ."'" );
		                tep_db_query(
							"DELETE FROM `". TABLE_ORDERS_TOTAL ."` WHERE `orders_id` = '". (int)$order_id ."'" );
						tep_db_query(
							"DELETE FROM `". TABLE_ORDERS_STATUS_HISTORY ."` WHERE `orders_id` = '" . (int)$order_id ."'" );
		                tep_db_query(
							"DELETE FROM `". TABLE_ORDERS_PRODUCTS ."` WHERE `orders_id` = '" . (int)$order_id ."'" );
		                tep_db_query(
							"DELETE FROM `". TABLE_ORDERS_PRODUCTS_ATTRIBUTES ."` WHERE `orders_id` = '" . (int)$order_id ."'" );
		                tep_db_query(
							"DELETE FROM `". TABLE_ORDERS_PRODUCTS_DOWNLOAD ."` WHERE `orders_id` = '" . (int)$order_id ."'" );
                    }

                    $insert_order = true;
                }
            }
            else
            {
                $insert_order = true;
            }

            if( $insert_order == true )
            {
                $order_totals = array();
                if( is_array($order_total_modules->modules) )
                {
                    reset( $order_total_modules->modules );
                    while( list(, $value) = each($order_total_modules->modules) )
                    {
                        $class = substr( $value, 0, strrpos($value, '.') );
                        if( $GLOBALS[$class]->enabled )
                        {
                            for ( $i = 0, $n = sizeof($GLOBALS[$class]->output); $i < $n; $i++ )
                            {
                                if( tep_not_null($GLOBALS[$class]->output[$i]['title']) && tep_not_null($GLOBALS[$class]->
                                    output[$i]['text']) )
                                {
                                    $order_totals[] = array(
										'code' => $GLOBALS[$class]->code,
										'title' => $GLOBALS[$class]->output[$i]['title'],
										'text' => $GLOBALS[$class]->output[$i]['text'],
										'value' => $GLOBALS[$class]->output[$i]['value'],
										'sort_order' => $GLOBALS[$class]->sort_order
										);
                                }
                            }
                        }
                    }
                }

                global $payu_ord_status_id;
                $ord_status_check = tep_db_query(
					"SELECT * FROM `orders_status`
					WHERE `orders_status_name` = '". $this->status_text_before_redirect ."'" );
                $ord_row = tep_db_fetch_array( $ord_status_check );
                $payu_ord_status_id = $ord_row['orders_status_id'];

                // Update order with pending status.
                $sql_data_array = array(
					'customers_id' => $customer_id,
					'customers_name' => $order->customer['firstname'] . ' ' . $order->customer['lastname'],
					'customers_company' => $order->customer['company'],
					'customers_street_address' => $order->customer['street_address'],
                    'customers_suburb' => $order->customer['suburb'],
					'customers_city' => $order->customer['city'],
					'customers_postcode' => $order->customer['postcode'],
                    'customers_state' => $order->customer['state'],
					'customers_country' => $order->customer['country']['title'],
					'customers_telephone' => $order->customer['telephone'],
                    'customers_email_address' => $order->customer['email_address'],
                    'customers_address_format_id' => $order->customer['format_id'],
					'delivery_name' => $order->delivery['firstname'] . ' ' . $order->delivery['lastname'],
                    'delivery_company' => $order->delivery['company'],
					'delivery_street_address' => $order->delivery['street_address'],
					'delivery_suburb' => $order->delivery['suburb'],
                    'delivery_city' => $order->delivery['city'],
					'delivery_postcode' => $order->delivery['postcode'],
					'delivery_state' => $order->delivery['state'],
                    'delivery_country' => $order->delivery['country']['title'],
                    'delivery_address_format_id' => $order->delivery['format_id'],
					'billing_name' => $order->billing['firstname'] . ' ' . $order->billing['lastname'],
                    'billing_company' => $order->billing['company'],
					'billing_street_address' => $order->billing['street_address'],
					'billing_suburb' => $order->billing['suburb'],
                    'billing_city' => $order->billing['city'],
					'billing_postcode' => $order->billing['postcode'],
					'billing_state' => $order->billing['state'],
                    'billing_country' => $order->billing['country']['title'],
                    'billing_address_format_id' => $order->billing['format_id'],
					'payment_method' => $order->info['payment_method'],
					'cc_type' => $order->info['cc_type'],
					'cc_owner' => $order->info['cc_owner'],
					'cc_number' => $order->info['cc_number'],
					'cc_expires' => $order->info['cc_expires'],
					'date_purchased' => 'now()',
					'orders_status' => $payu_ord_status_id,
                    'currency' => $order->info['currency'],
					'currency_value' => $order->info['currency_value']
					);

                tep_db_perform( TABLE_ORDERS, $sql_data_array );
				$insert_id = tep_db_insert_id();

                for ( $i = 0, $n = sizeof($order_totals); $i < $n; $i++ )
                {
                    $sql_data_array = array(
						'orders_id' => $insert_id,
						'title' => $order_totals[$i]['title'],
                        'text' => $order_totals[$i]['text'],
						'value' => $order_totals[$i]['value'],
                        'class' => $order_totals[$i]['code'],
						'sort_order' => $order_totals[$i]['sort_order']
						);

                    tep_db_perform( TABLE_ORDERS_TOTAL, $sql_data_array );
                }

                for ( $i = 0, $n = sizeof($order->products); $i < $n; $i++ )
                {
                    $sql_data_array = array(
						'orders_id' => $insert_id,
						'products_id' => tep_get_prid( $order->products[$i]['id'] ),
						'products_model' => $order->products[$i]['model'],
                        'products_name' => $order->products[$i]['name'],
						'products_price' => $order->products[$i]['price'],
						'final_price' => $order->products[$i]['final_price'],
                        'products_tax' => $order->products[$i]['tax'],
						'products_quantity' => $order->products[$i]['qty']
						);

                    tep_db_perform( TABLE_ORDERS_PRODUCTS, $sql_data_array );

                    $order_products_id = tep_db_insert_id();

                    $attributes_exist = '0';
                    if( isset($order->products[$i]['attributes']) )
                    {
                        $attributes_exist = '1';
                        for ( $j = 0, $n2 = sizeof($order->products[$i]['attributes']); $j < $n2; $j++ )
                        {
                            if( DOWNLOAD_ENABLED == 'true' )
                            {
                                $attributes_query =
									"SELECT popt.products_options_name, poval.products_options_values_name,
										pa.options_values_price, pa.price_prefix, pad.products_attributes_maxdays,
										pad.products_attributes_maxcount , pad.products_attributes_filename
                                    FROM " . TABLE_PRODUCTS_OPTIONS ." popt,
										". TABLE_PRODUCTS_OPTIONS_VALUES ." poval,
										". TABLE_PRODUCTS_ATTRIBUTES ." pa
                                       LEFT JOIN ". TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD ." pad ON pa.products_attributes_id = pad.products_attributes_id
                                    WHERE pa.products_id = '". $order->products[$i]['id'] ."'
                                       AND pa.options_id = '". $order->products[$i]['attributes'][$j]['option_id'] ."'
                                       AND pa.options_id = popt.products_options_id
                                       AND pa.options_values_id = '". $order->products[$i]['attributes'][$j]['value_id'] ."'
                                       AND pa.options_values_id = poval.products_options_values_id
                                       AND popt.language_id = '". $languages_id ."'
                                       AND poval.language_id = '". $languages_id ."'";
                                $attributes = tep_db_query( $attributes_query );
                            }
                            else
                            {
                                $attributes = tep_db_query(
									"SELECT popt.products_options_name, poval.products_options_values_name,
										pa.options_values_price, pa.price_prefix
									FROM ". TABLE_PRODUCTS_OPTIONS ." popt,
										". TABLE_PRODUCTS_OPTIONS_VALUES ." poval,
										". TABLE_PRODUCTS_ATTRIBUTES . " pa
									WHERE pa.products_id = '" . $order->products[$i]['id'] ."'
										AND pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] ."'
										AND pa.options_id = popt.products_options_id
										AND pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] ."'
										AND pa.options_values_id = poval.products_options_values_id
										AND popt.language_id = '". $languages_id . "'
										AND poval.language_id = '" . $languages_id . "'" );
                            }
                            $attributes_values = tep_db_fetch_array( $attributes );

                            $sql_data_array = array(
								'orders_id' => $insert_id,
								'orders_products_id' => $order_products_id,
                                'products_options' => $attributes_values['products_options_name'],
                                'products_options_values' => $attributes_values['products_options_values_name'],
                                'options_values_price' => $attributes_values['options_values_price'],
                                'price_prefix' => $attributes_values['price_prefix']
								);

                            tep_db_perform( TABLE_ORDERS_PRODUCTS_ATTRIBUTES, $sql_data_array );

                            if( (DOWNLOAD_ENABLED == 'true') &&
								isset($attributes_values['products_attributes_filename']) &&
                                tep_not_null($attributes_values['products_attributes_filename']) )
                            {
                                $sql_data_array = array(
									'orders_id' => $insert_id,
									'orders_products_id' => $order_products_id,
                                    'orders_products_filename' => $attributes_values['products_attributes_filename'],
                                    'download_maxdays' => $attributes_values['products_attributes_maxdays'],
                                    'download_count' => $attributes_values['products_attributes_maxcount']
									);

                                tep_db_perform( TABLE_ORDERS_PRODUCTS_DOWNLOAD, $sql_data_array );
                            }
                        }
                    }
                }
                $cart_PayU_ID = $cartID . '-' . $insert_id;
                tep_session_register( 'cart_PayU_ID' );
            }
        }


        return false;
    }


	/**
     * process_button
     *
     * Returns the extra form data in checkout_confirmation. Used to pass data
	 * to payu as hidden fields in a POST-type form string $form_action_url.
	 * The target of the form for the confirmation button in
	 * checkout_confirmation.php is used to select the address to post to.
	 *
	 * >> Called by checkout_confirmation.php
     */    
	
    function process_button() {
		global $customer_id, $cart, $order, $sendto, $currency;
        global $cart_ID, $payu_ord_status_id,$cart_PayU_ID;
		
		$process_button_string = '';		
        $process_button_string = tep_draw_hidden_field( 'cart_ID',  $cart->cartID).tep_draw_hidden_field( 'cart_PayU_ID',  $cart_PayU_ID);

        return $process_button_string;
    }
	

    function finaliseOrder($orderId,$payuReference)
    {
		global $customer_id, $order, $order_totals, $sendto, $billto,$languages_id, $payment, $currencies, $cart, $cart_PayU_ID;


		list($junk,$orderId) = explode('-',$_SESSION['cart_PayU_ID'],2);
		$order_query = tep_db_query(
		    "SELECT `orders_status`, `currency`, `currency_value`
		    FROM `". TABLE_ORDERS ."`
		    WHERE `orders_id` = '" . $orderId . "'
			" );
		

		// If order found
		if( tep_db_num_rows( $order_query ) > 0 )
		{
			$ord_status_check = tep_db_query(
				"SELECT * FROM `orders_status`
					WHERE `orders_status_name` = '". $this->status_text_successful ."'" );
            $ord_row = tep_db_fetch_array( $ord_status_check );
            $successfulStatusId = $ord_row['orders_status_id'];

			$ord_status_check = tep_db_query(
				"SELECT * FROM `orders_status`
					WHERE `orders_status_name` = '". $this->status_text_before_redirect ."'" );
            $ord_row = tep_db_fetch_array( $ord_status_check );
            $pendingStatusId = $ord_row['orders_status_id'];

			// Get order details
		    $order = tep_db_fetch_array( $order_query );

		    if( $order['orders_status'] == $pendingStatusId )
		    {
		        $sql_data_array = array(
		            'orders_id' => $orderId,
		            'orders_status_id' => $successfulStatusId,
		            'date_added' => 'now()',
		            'customer_notified' => '0',
		            'comments' => '' );

		        tep_db_perform( TABLE_ORDERS_STATUS_HISTORY, $sql_data_array );

		        // Update order status
		        tep_db_query(
		            "UPDATE ". TABLE_ORDERS ."
		            SET `orders_status` = '". $successfulStatusId . "',
		              `last_modified` = NOW()
		            WHERE `orders_id` = '". $orderId ."'" );
		    }
		}

		// initialized for the email confirmation
		$products_ordered = '';
		$subtotal = 0;
		$total_tax = 0;

		for ($i=0, $n=sizeof($order->products); $i<$n; $i++) {
		// Stock Update - Joao Correia
		if (STOCK_LIMITED == 'true') {
		  if (DOWNLOAD_ENABLED == 'true') {
		    $stock_query_raw = "SELECT products_quantity, pad.products_attributes_filename
		                        FROM " . TABLE_PRODUCTS . " p
		                        LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES . " pa
		                        ON p.products_id=pa.products_id
		                        LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
		                        ON pa.products_attributes_id=pad.products_attributes_id
		                        WHERE p.products_id = '" . tep_get_prid($order->products[$i]['id']) . "'";
		// Will work with only one option for downloadable products
		// otherwise, we have to build the query dynamically with a loop
		    $products_attributes = $order->products[$i]['attributes'];
		    if (is_array($products_attributes)) {
		      $stock_query_raw .= " AND pa.options_id = '" . $products_attributes[0]['option_id'] . "' AND pa.options_values_id = '" . $products_attributes[0]['value_id'] . "'";
		    }
		    $stock_query = tep_db_query($stock_query_raw);
		  } else {
		    $stock_query = tep_db_query("select products_quantity from " . TABLE_PRODUCTS . " where products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");
		  }
		  if (tep_db_num_rows($stock_query) > 0) {
		    $stock_values = tep_db_fetch_array($stock_query);
		// do not decrement quantities if products_attributes_filename exists
		    if ((DOWNLOAD_ENABLED != 'true') || (!$stock_values['products_attributes_filename'])) {
		      $stock_left = $stock_values['products_quantity'] - $order->products[$i]['qty'];
		    } else {
		      $stock_left = $stock_values['products_quantity'];
		    }
		    tep_db_query("update " . TABLE_PRODUCTS . " set products_quantity = '" . $stock_left . "' where products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");
		    if ( ($stock_left < 1) && (STOCK_ALLOW_CHECKOUT == 'false') ) {
		      tep_db_query("update " . TABLE_PRODUCTS . " set products_status = '0' where products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");
		    }
		  }
		}

		// Update products_ordered (for bestsellers list)
		tep_db_query("update " . TABLE_PRODUCTS . " set products_ordered = products_ordered + " . sprintf('%d', $order->products[$i]['qty']) . " where products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");

		//------insert customer choosen option to order--------
		$attributes_exist = '0';
		$products_ordered_attributes = '';
		if (isset($order->products[$i]['attributes'])) {
		  $attributes_exist = '1';
		  for ($j=0, $n2=sizeof($order->products[$i]['attributes']); $j<$n2; $j++) {
		    if (DOWNLOAD_ENABLED == 'true') {
		      $attributes_query = "select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix, pad.products_attributes_maxdays, pad.products_attributes_maxcount , pad.products_attributes_filename
		                           from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa
		                           left join " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
		                           on pa.products_attributes_id=pad.products_attributes_id
		                           where pa.products_id = '" . $order->products[$i]['id'] . "'
		                           and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "'
		                           and pa.options_id = popt.products_options_id
		                           and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "'
		                           and pa.options_values_id = poval.products_options_values_id
		                           and popt.language_id = '" . $languages_id . "'
		                           and poval.language_id = '" . $languages_id . "'";
		      $attributes = tep_db_query($attributes_query);
		    } else {
		      $attributes = tep_db_query("select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa where pa.products_id = '" . $order->products[$i]['id'] . "' and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "' and pa.options_id = popt.products_options_id and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "' and pa.options_values_id = poval.products_options_values_id and popt.language_id = '" . $languages_id . "' and poval.language_id = '" . $languages_id . "'");
		    }
		    $attributes_values = tep_db_fetch_array($attributes);

		    $products_ordered_attributes .= "\n\t" . $attributes_values['products_options_name'] . ' ' . $attributes_values['products_options_values_name'];
		  }
		}
		//------insert customer choosen option eof ----
		$total_weight += ($order->products[$i]['qty'] * $order->products[$i]['weight']);
		$total_tax += tep_calculate_tax($total_products_price, $products_tax) * $order->products[$i]['qty'];
		$total_cost += $total_products_price;

		$products_ordered .= $order->products[$i]['qty'] . ' x ' . $order->products[$i]['name'] . ' (' . $order->products[$i]['model'] . ') = ' . $currencies->display_price($order->products[$i]['final_price'], $order->products[$i]['tax'], $order->products[$i]['qty']) . $products_ordered_attributes . "\n";
		}

		// lets start with the email confirmation
		$email_order = STORE_NAME . "\n" .
		             EMAIL_SEPARATOR . "\n" .
		             EMAIL_TEXT_ORDER_NUMBER . ' ' . $order_id . "\n" .
		             EMAIL_TEXT_INVOICE_URL . ' ' . tep_href_link(FILENAME_ACCOUNT_HISTORY_INFO, 'order_id=' . $order_id, 'SSL', false) . "\n" .
		             EMAIL_TEXT_DATE_ORDERED . ' ' . strftime(DATE_FORMAT_LONG) . "\n\n";
		if ($order->info['comments']) {
		$email_order .= tep_db_output($order->info['comments']) . "\n\n";
		}
		$email_order .= EMAIL_TEXT_PRODUCTS . "\n" .
		              EMAIL_SEPARATOR . "\n" .
		              $products_ordered .
		              EMAIL_SEPARATOR . "\n";

		for ($i=0, $n=sizeof($order_totals); $i<$n; $i++) {
		$email_order .= strip_tags($order_totals[$i]['title']) . ' ' . strip_tags($order_totals[$i]['text']) . "\n";
		}

		if ($order->content_type != 'virtual') {
		$email_order .= "\n" . EMAIL_TEXT_DELIVERY_ADDRESS . "\n" .
		                EMAIL_SEPARATOR . "\n" .
		                tep_address_label($customer_id, $sendto, 0, '', "\n") . "\n";
		}

		$email_order .= "\n" . EMAIL_TEXT_BILLING_ADDRESS . "\n" .
		              EMAIL_SEPARATOR . "\n" .
		              tep_address_label($customer_id, $billto, 0, '', "\n") . "\n\n";

		if (is_object($$payment)) {
		$email_order .= EMAIL_TEXT_PAYMENT_METHOD . "\n" .
		                EMAIL_SEPARATOR . "\n";
		$payment_class = $$payment;
		$email_order .= $payment_class->title . "\n\n";
		if ($payment_class->email_footer) {
		  $email_order .= $payment_class->email_footer . "\n\n";
		}
		}

		tep_mail($order->customer['firstname'] . ' ' . $order->customer['lastname'], $order->customer['email_address'], EMAIL_TEXT_SUBJECT, $email_order, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);

		// send emails to other people
		if (SEND_EXTRA_ORDER_EMAILS_TO != '') {
		tep_mail('', SEND_EXTRA_ORDER_EMAILS_TO, EMAIL_TEXT_SUBJECT, $email_order, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
		}

		// load the after_process function from the payment modules
		
		$cart->reset(true);

		// unregister session variables used during checkout
		tep_session_unregister('sendto');
		tep_session_unregister('billto');
		tep_session_unregister('shipping');
		tep_session_unregister('payment');
		tep_session_unregister('comments');
		tep_session_unregister( 'cart_PayU_ID' );
    }

	function doPayuSetTransaction($cart,$order,$postVars,$orderID) {	

		$safeKey = MODULE_PAYMENT_PAYU_MEA_REDIRECTPAYMENTPAGE_SAFEKEY;
		$soapUsername = MODULE_PAYMENT_PAYU_MEA_REDIRECTPAYMENTPAGE_SOAP_USERNAME;
		$soapPassword = MODULE_PAYMENT_PAYU_MEA_REDIRECTPAYMENTPAGE_SOAP_PASSWORD;
		$merchantReference	= $orderID;	
		$constructorArray = array();
		
		if( strtolower(MODULE_PAYMENT_PAYU_MEA_REDIRECTPAYMENTPAGE_ENVIRONMENT) == 'production' ){
			$constructorArray['production'] = true;
		} else {
			require_once(dirname(__FILE__)."/../../library.payu/inc.demo/config.demo.php");						
			$soapUsername = $rpp['username'];
			$soapPassword = $rpp['password'];
			$safeKey = $rpp['Safekey'];			
		}
		
		$constructorArray['username'] = $soapUsername;
        $constructorArray['password'] = $soapPassword;        		
		
		$setTransactionArray = array();
		//$setTransactionArray['Api'] = $apiVersion;
		$setTransactionArray['Safekey'] = $safeKey;
		$setTransactionArray['TransactionType'] = MODULE_PAYMENT_PAYU_MEA_REDIRECTPAYMENTPAGE_TRANSACTION_TYPE;		
		//$setTransactionArray['TransactionType'] = 'RESERVE';

		$setTransactionArray['AdditionalInformation']['merchantReference'] = $merchantReference;
		//$setTransactionArray['AdditionalInformation']['demoMode'] = 'true';
		$setTransactionArray['AdditionalInformation']['cancelUrl'] = tep_href_link('ext/modules/payment/payuMeaRedirectPaymentPage/cancel.php?cartID='.$postVars['cart_PayU_ID'], '', 'SSL', false, false);
		//$setTransactionArray['AdditionalInformation']['returnUrl'] = MODULE_PAYMENT_PAYU_MEA_REDIRECTPAYMENTPAGE_RETURN_URL;
		$setTransactionArray['AdditionalInformation']['returnUrl'] = tep_href_link('ext/modules/payment/payuMeaRedirectPaymentPage/response.php?cartID='.$postVars['cart_PayU_ID'], '', 'SSL', false, false);
		$setTransactionArray['AdditionalInformation']['supportedPaymentMethods'] = MODULE_PAYMENT_PAYU_MEA_REDIRECTPAYMENTPAGE_PAYMENT_METHOD;
		
		$setTransactionArray['Basket']['description'] = MODULE_PAYMENT_PAYU_MEA_REDIRECTPAYMENTPAGE_INVOICE_DESCRIPTION.$merchantReference;
		$setTransactionArray['Basket']['amountInCents'] = $order['info']['total']*100;
		$setTransactionArray['Basket']['amountInCents'] = (int) $setTransactionArray['Basket']['amountInCents'];
		$setTransactionArray['Basket']['currencyCode'] = 'ZAR';

		//$setTransactionArray['Customer']['merchantUserId'] = "7";
		$setTransactionArray['Customer']['email'] = stripslashes($order['customer']['email_address']);
		$setTransactionArray['Customer']['firstName'] = stripslashes($order['delivery']['firstname']);
		$setTransactionArray['Customer']['lastName'] = stripslashes($order['delivery']['lastname']);
		$setTransactionArray['Customer']['mobile'] = stripslashes($order['customer']['telephone']);
		//$setTransactionArray['Customer']['regionalId'] = '1234512345122';
		//$setTransactionArray['Customer']['countryCode'] = '27';
		

		if(strtolower($constructorArray['logEnable']) == "true") {
            $constructorArray['logEnable'] = true;
        } 
        else {
            $constructorArray['logEnable'] = false;
        }
        if(strtolower($constructorArray['extendedDebugEnable']) == "true") {
            $constructorArray['extendedDebugEnable'] = true;
        }
        else {
            $constructorArray['extendedDebugEnable'] = false;
        }
		
		//var_dump($setTransactionArray);
		//var_dump($constructorArray);
		//die();
		
		$payuRppInstance = new PayuRedirectPaymentPage($constructorArray);
		$setTransactionResponse = $payuRppInstance->doSetTransactionSoapCall($setTransactionArray);

		return $setTransactionResponse;

	}

	function cancelTransaction($cartID,$payuReference,$postVars) {	

	}

	
	function updateOrderStatusHistory ($orderId, $comments, $orderStatusId = null, $notifyCustomer = 0) {
		
		$orderCheck = tep_db_query(
								"SELECT * FROM `".TABLE_ORDERS."`
								WHERE `orders_id` = '". $orderId ."'" );
		if( tep_db_num_rows( $orderCheck ) > 0 )
		{
			$thisOrder = tep_db_fetch_array( $orderCheck );
			$orderCurrentStatusId = $thisOrder['orders_status'];

			if(is_null($orderStatusId)) {
				$orderStatusId = $orderCurrentStatusId;
			}

			if($notifyCustomer != 0) {
				$notifyCustomer = 1;
			}
			
			$sql_data_array = array(
					'orders_id' => $orderId,
					'orders_status_id' => $orderStatusId ,
					'date_added' => 'now()',
					'customer_notified' => $notifyCustomer,
					'comments' => $comments 
					);
			tep_db_perform( TABLE_ORDERS_STATUS_HISTORY, $sql_data_array );
		}			
	}

	function doPayuGetTransaction($payuReference) {	
		
		$safeKey = MODULE_PAYMENT_PAYU_MEA_REDIRECTPAYMENTPAGE_SAFEKEY;
		$soapUsername = MODULE_PAYMENT_PAYU_MEA_REDIRECTPAYMENTPAGE_SOAP_USERNAME;
		$soapPassword = MODULE_PAYMENT_PAYU_MEA_REDIRECTPAYMENTPAGE_SOAP_PASSWORD;
		$merchantReference	= $cartID;	
					
		if( strtolower(MODULE_PAYMENT_PAYU_MEA_REDIRECTPAYMENTPAGE_ENVIRONMENT) == 'production' ){
			$constructorArray['production'] = true;
		} else {
			require_once(dirname(__FILE__)."/../../library.payu/inc.demo/config.demo.php");						
			$soapUsername = $rpp['username'];
			$soapPassword = $rpp['password'];
			$safeKey = $rpp['Safekey'];			
		}

		$transactionSuccess = 0;
        try {
            
            //Creating get transaction soap data array
            $getTransactionSoapDataArray = array();
            $getTransactionSoapDataArray['Safekey'] = $safeKey;
            $getTransactionSoapDataArray['AdditionalInformation']['payUReference'] = $payuReference;        

            //Creating constructor array for the payURedirect and instantiating 
            $constructorArray = array();
            $constructorArray['username'] = $soapUsername;
            $constructorArray['password'] = $soapPassword;
            //$constructorArray['logEnable'] = (bool) get_option('payuRedirect_enableLogging');
            //$constructorArray['extendedDebugEnable'] = get_option('payuRedirect_enableExtendedDebug');
            if(strtolower($constructorArray['logEnable']) == "true") {
                $constructorArray['logEnable'] = true;
            } 
            else {
                $constructorArray['logEnable'] = false;
            }
            if(strtolower($constructorArray['extendedDebugEnable']) == "true") {
                $constructorArray['extendedDebugEnable'] = true;
            } 
            else {
                $constructorArray['extendedDebugEnable'] = false;
            }

            $payuRppInstance = new PayuRedirectPaymentPage($constructorArray);
            $getTransactionResponse = $payuRppInstance->doGetTransactionSoapCall($getTransactionSoapDataArray); 
            
			//Set merchant reference
            if( isset($getTransactionResponse['soapResponse']['merchantReference']) && !empty($getTransactionResponse['soapResponse']['merchantReference']) ) {
                $MerchantReference = $getTransactionResponse['soapResponse']['merchantReference'];
            }
            
            //Checking the response from the SOAP call to see if successfull
            if(isset($getTransactionResponse['soapResponse']['successful']) && ($getTransactionResponse['soapResponse']['successful']  === true)) {

                if(isset($getTransactionResponse['soapResponse']['transactionType']) && (strtolower($getTransactionResponse['soapResponse']['transactionType']) == 'reserve') ) {
                    if(isset($getTransactionResponse['soapResponse']['transactionState']) && (strtolower($getTransactionResponse['soapResponse']['transactionState']) == 'successful') ){                    
                        $transactionSuccess = 1; //funds reserved need to finalize in the admin box                    
                    }            
                }
                if(isset($getTransactionResponse['soapResponse']['transactionType']) && (strtolower($getTransactionResponse['soapResponse']['transactionType']) == 'payment') ) {                    
                    if(isset($getTransactionResponse['soapResponse']['transactionState']) && (strtolower($getTransactionResponse['soapResponse']['transactionState']) == 'successful') ) {                    
						$transactionSuccess = 1;
                    }            
                }            
                else {
					if(!empty($getTransactionResponse['soapResponse']['displayMessage'])) {
	                    $errorMessage = $getTransactionResponse['soapResponse']['displayMessage'];
					}
					else {
					    $errorMessage = $getTransactionResponse['soapResponse']['resultMessage'];
					}
                }
            }
            else {
				if(!empty($getTransactionResponse['soapResponse']['displayMessage'])) {
                    $errorMessage = $getTransactionResponse['soapResponse']['displayMessage'];
				}
				else {
				    $errorMessage = $getTransactionResponse['soapResponse']['resultMessage'];
				}
            }
        }
        catch(Exception $e) {
            $errorMessage = $e->getMessage();            
        }    
        
		$returnArray = array(
							'success'=>0,
							'errorMessage'=> $errorMessage,
							);

        //Now doing db updates for the orders 
        if($transactionSuccess == 1)
        {
			$returnArray['success'] = 1;
			$returnArray['soapResponse'] = $getTransactionResponse['soapResponse'];
			unset($returnArray['errorMessage']);			                
        }    
		return $returnArray;				        
	}
	
    function after_process() {
		return false;
    }
	
    function get_error() {		
		global $HTTP_GET_VARS;
		$error = array('error' => 'ERROR: '.stripslashes(urldecode($HTTP_GET_VARS['error'])));
		return $error;
	}

    function check() {	
		if (!isset($this->_check)) {
			$check_query = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_PAYU_MEA_REDIRECTPAYMENTPAGE_STATUS'");
			$this->_check = tep_db_num_rows($check_query);
		}		
		return $this->_check;
    }

    function install() {  	  			
	
		$payScreenTitle = "Credit Card (Processed By PayU)";
	
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Gateway Enable', 'MODULE_PAYMENT_PAYU_MEA_REDIRECTPAYMENTPAGE_STATUS', 'False', '* Only enable if you have all the required values.', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Title', 'MODULE_PAYMENT_PAYU_MEA_REDIRECTPAYMENTPAGE_TITLE', '".$payScreenTitle."', '* The text the shopper will see when selecting where to pay.', '6', '0', now())");	  
		tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Environment?*', 'MODULE_PAYMENT_PAYU_MEA_REDIRECTPAYMENTPAGE_ENVIRONMENT', 'Staging', '* Which payu environment to use for transactions. (Staging = Test mode)', '6', '1', 'tep_cfg_select_option(array(\'Staging\', \'Production\'), ', now())");	 	 
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('SafeKey*', 'MODULE_PAYMENT_PAYU_MEA_REDIRECTPAYMENTPAGE_SAFEKEY', '', '* Safekey used in transactions.', '6', '0', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('SOAP Username*', 'MODULE_PAYMENT_PAYU_MEA_REDIRECTPAYMENTPAGE_SOAP_USERNAME', '', '* SOAP API username used in transactions.', '6', '0', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('SOAP Password*', 'MODULE_PAYMENT_PAYU_MEA_REDIRECTPAYMENTPAGE_SOAP_PASSWORD', '', '* SOAP API Password used in transactions.', '6', '0', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Transaction Type*', 'MODULE_PAYMENT_PAYU_MEA_REDIRECTPAYMENTPAGE_TRANSACTION_TYPE', 'PAYMENT', '* Transaction type used for transactions.', '6', '0','tep_cfg_select_option(array(\'PAYMENT\', \'RESERVE\'), ', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Payment Method*', 'MODULE_PAYMENT_PAYU_MEA_REDIRECTPAYMENTPAGE_PAYMENT_METHOD', 'CREDITCARD', '* Payment method used for transactions.', '6', '0', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Billing Currency*', 'MODULE_PAYMENT_PAYU_MEA_REDIRECTPAYMENTPAGE_BILLING_CURRENCY', 'ZAR', '* Currency used in transactions.', '6', '0', now())");	
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('PayU Invoice Description Prepend', 'MODULE_PAYMENT_PAYU_MEA_REDIRECTPAYMENTPAGE_INVOICE_DESCRIPTION', 'Store Order Number:', '* This value is added before the order number and sent to Payu with transactions and will display in invoices.', '6', '0', now())");
		//tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Return Url*', 'MODULE_PAYMENT_PAYU_MEA_REDIRECTPAYMENTPAGE_RETURN_URL', '', 'The return url after successful credit card processing.', '6', '0', now())");
		//tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Cancel Url*', 'MODULE_PAYMENT_PAYU_MEA_REDIRECTPAYMENTPAGE_CANCEL_URL', '', 'The cancel url if cancel button was pressed.', '6', '0', now())");
		//tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_PAYMENT_PAYU_MEA_REDIRECTPAYMENTPAGE_SORT_ORDER', '0', '', '6', '0', now())"); 	   
	}

    function remove() {
		tep_db_query("delete from " . TABLE_CONFIGURATION . " WHERE `configuration_key` LIKE 'MODULE_PAYMENT_PAYU_MEA_REDIRECTPAYMENTPAGE%'");	  
    }

	function keys() { 
		return array(
				'MODULE_PAYMENT_PAYU_MEA_REDIRECTPAYMENTPAGE_STATUS',
				'MODULE_PAYMENT_PAYU_MEA_REDIRECTPAYMENTPAGE_TITLE',
				'MODULE_PAYMENT_PAYU_MEA_REDIRECTPAYMENTPAGE_ENVIRONMENT',
				'MODULE_PAYMENT_PAYU_MEA_REDIRECTPAYMENTPAGE_SAFEKEY',
				'MODULE_PAYMENT_PAYU_MEA_REDIRECTPAYMENTPAGE_SOAP_USERNAME',
				'MODULE_PAYMENT_PAYU_MEA_REDIRECTPAYMENTPAGE_SOAP_PASSWORD',
				'MODULE_PAYMENT_PAYU_MEA_REDIRECTPAYMENTPAGE_TRANSACTION_TYPE',
				'MODULE_PAYMENT_PAYU_MEA_REDIRECTPAYMENTPAGE_PAYMENT_METHOD',
				'MODULE_PAYMENT_PAYU_MEA_REDIRECTPAYMENTPAGE_BILLING_CURRENCY',
				'MODULE_PAYMENT_PAYU_MEA_REDIRECTPAYMENTPAGE_INVOICE_DESCRIPTION',
				);
	}


    // format prices without currency formatting
    function format_raw($number, $currency_code = '', $currency_value = '') {
		global $currencies, $currency;

		if (empty($currency_code) || !$this->is_set($currency_code)) {
			$currency_code = $currency;
		}

		if (empty($currency_value) || !is_numeric($currency_value)) {
			$currency_value = $currencies->currencies[$currency_code]['value'];
		}

		return number_format(tep_round($number * $currency_value, $currencies->currencies[$currency_code]['decimal_places']), $currencies->currencies[$currency_code]['decimal_places'], '.', '');
	}
	
}

