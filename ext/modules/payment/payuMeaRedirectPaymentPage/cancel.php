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

	list($cartId,$orderId) = explode('-',$_GET['cartID'],2);

	$comment = "Payment cancelled\r\n";
	$rppInstance->updateOrderStatusHistory($orderId, $comment); 

	tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error=' . $rppInstance->code . '&error=' . tep_output_string_protected('Payment cancelled'), 'SSL', true, false));	
}
catch(Exception $e) {
	tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error=' . $rppInstance->code . '&error=' . tep_output_string_protected('Payment cancelled'), 'SSL', true, false));	
}
require('includes/application_bottom.php');
die();
