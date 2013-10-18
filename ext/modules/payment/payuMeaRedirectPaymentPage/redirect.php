<?php
//@session_start();
ob_start();
chdir( '../../../../' );
require( 'includes/application_top.php' );

require_once(dirname(__FILE__)."/../../../../includes/modules/payment/payu_mea_redirectPaymentPage.php");
$rppInstance = new payu_mea_redirectPaymentPage();

try{
	
	if($_POST['cart_ID'] != $cart->cartID) {
		throw new Exception("Invalid cart specified for processing");
	}
	elseif($_POST['cart_PayU_ID'] != $_SESSION['cart_PayU_ID']) {
		throw new Exception("Invalid cart Specified for processing");
	}

	list($junk,$orderID) = explode('-',$_SESSION['cart_PayU_ID'],2);

	$returnData = $rppInstance->doPayuSetTransaction($cart,$_SESSION['ss_order'],$_POST,$orderID); 

	if(isset($returnData['redirectPaymentPageUrl'])) {		        
		$comment = " Redirecting to PayU Payment Page with PayU Ref:".$returnData["soapResponse"]["payUReference"];
		$rppInstance->updateOrderStatusHistory($orderID, $comment); 
		tep_redirect( $returnData['redirectPaymentPageUrl'] , '', 'SSL' );
    }
    else {
		$error = "Unable to connect to PayU. Please contact merchant";
		$comment = " Error while trying to redirect to PayU for payment:\r\n";
		$comment .= "Reason: ".$error;
		$rppInstance->updateOrderStatusHistory($orderID, $comment); 
		     
		tep_redirect( tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error=' . $rppInstance->code . '&error=' . tep_output_string_protected($error)) , '', 'SSL' );
    }
	
}
catch(Exception $e) {

	$error = $e->getMessage();
	$comment = " Error while trying to redirect to PayU for payment:\r\n";
	$comment .= "Reason: ".$error;
	$rppInstance->updateOrderStatusHistory($orderID, $comment); 
	     
	tep_redirect( tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error=' . $rppInstance->code . '&error=' . tep_output_string_protected($error)) , '', 'SSL' );

}
require('includes/application_bottom.php');
die();

