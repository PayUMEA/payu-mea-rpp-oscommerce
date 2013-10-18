<?php
//@session_start();
ob_start();
chdir( '../../../../' );
require( 'includes/application_top.php' );

require_once(dirname(__FILE__)."/../../../../includes/modules/payment/payu_mea_redirectPaymentPage.php");
$rppInstance = new payu_mea_redirectPaymentPage();

try{
	if($_GET['cartID'] != $_SESSION['cart_PayU_ID']) {
		throw new Exception("Invalid Cart Specified for processing");
	}

	$returnArray = $rppInstance->doPayuGetTransaction($_GET['PayUReference']); 	
	
	list($cartId,$orderId) = explode('-',$_GET['cartID'],2);

	if($returnArray['success'] == 1) {
		
		$rppInstance->finaliseOrder($orderId);
		
		$comment = "Payment successfully processed by PayU\r\n";;
		$comment .= "PayU Reference:".$_GET['PayUReference']."\r\n";
		$comment .= "Gateway Reference:".$returnArray["soapResponse"]["paymentMethodsUsed"]["gatewayReference"]."\r\n";
		$comment .= "Amount paid (in cents):".$returnArray["soapResponse"]["paymentMethodsUsed"]["amountInCents"]."\r\n";
		$rppInstance->updateOrderStatusHistory($orderId, $comment); 
		
		tep_redirect( tep_href_link(FILENAME_CHECKOUT_SUCCESS, '', 'SSL', false, false) , '', 'SSL' );
	}	
	else {
		$comment = "Payment Failed\r\n";;
		$comment .= "PayU Reference:".$_GET['PayUReference']."\r\n";
		$comment .= "Error Message: ".$returnArray['errorMessage']."\r\n";
		$rppInstance->updateOrderStatusHistory($orderId, $comment); 
		tep_redirect( tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error=' . $rppInstance->code . '&error=' . tep_output_string_protected($returnArray['errorMessage'])) , '', 'SSL' );
	}
}
catch(Exception $e) {
	$comment = "Payment Failed\r\n";;
	$comment .= "PayU Reference:".$_GET['PayUReference']."\r\n";
	$comment .= "Error Message: ".$e->getMessage()."\r\n";
	$rppInstance->updateOrderStatusHistory($orderId, $comment); 
	tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error=' . $rppInstance->code . '&error=' . tep_output_string_protected($e->getMessage()), 'SSL', true, false));
}
require('includes/application_bottom.php');
die();
