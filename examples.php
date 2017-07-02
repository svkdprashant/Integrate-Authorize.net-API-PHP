<?php
	
/*
 ----------------------------------------------------------------
 Author: Prashant Jethwa
 Purpose: Sample code on 
 			1. Add Credit Card
 			2. Add eCheck
 			3. Charge Customer
 			4. Get credit card / eCheck details
 			5. Refund amount to customer
 			6. Get Transaction status
 			7. Delete credit card / echeck (Delete customer payment profile)
 			8. Delete customer
 Email: codebucket.co@gmail.com
 Last Modified on 19 May 2017
 -----------------------------------------------------------------
*/

include_once('authorize.class.php');

// Create an object of AuthorizeAPI class
$objAuthorizeAPI = new AuthorizeAPI('7mMJ5tx5M', '37U3eM9qR7Td5qEg');

// Set customer's information
$arrCustomerInfo = array();
$arrCustomerInfo['firstname'] = 'Prashant';
$arrCustomerInfo['lastname'] = 'Jethwa';
$arrCustomerInfo['company_name'] = 'Code Bucket';
$arrCustomerInfo['ad_street'] = 'Jodhpur Crossroad';
$arrCustomerInfo['ad_city'] = 'Ahmedabad';
$arrCustomerInfo['ad_state'] = 'Gujarat';
$arrCustomerInfo['ad_zip'] = '380015';
$arrCustomerInfo['ad_country'] = 'India';
$arrCustomerInfo['ph_number'] = '123456791';
$arrCustomerInfo['em_email'] = 'codebucket.co@gmail.com';

$objAuthorizeAPI->setCustomerAddress($arrCustomerInfo);

// ~~~~~~~~~~~~ ADD CREDIT CARD ~~~~~~~~~~~~~~~~~~~~~~ //
$objAuthorizeAPI->setCreditCardParameters('370000000000002', '2019-12', '123');

$arrCustomerAddCCResponse = $objAuthorizeAPI->addCustomerPaymentProfile(111,'cc');

/* 
Response will be

{"success":"1","customerProfileId":"1501067535","customerPaymentProfileId":"1500625311","error":"","paymentFlag":"1","message":""}
  
Store Customer's profile id and payment profile id in database

If You want to add Second Credit card / eCheck for same above customer then call addCustomerPaymentProfile as below
$arrCustomerAddResponse = $objAuthorizeAPI->addCustomerPaymentProfile(111,'cc', true, 1501067535);
*/


// ~~~~~~~~~~~~~~~~~~~~~~~~ ADD eCheck ~~~~~~~~~~~~~~~~~~~~~ //
$objAuthorizeAPI->setBankParameters('071921891', '123456789', 'Prashant Jethwa', 'savings');
$arrCustomerAddeCheckResponse = $objAuthorizeAPI->addCustomerPaymentProfile(111,'eCheck',true, 1501067535);


// ~~~~~~~~~~~~~~~~~~~~~~~~ CHARGE CUSTOMER ~~~~~~~~~~~~~~~~~~~~~ //
$arrChargeResponse = $objAuthorizeAPI->chargeCCeCheck(1501067535,1500625311, 50.15);


// ~~~~~~~~~~~~~~~~~~~~~~~~ GET CREDIT CARD / ECHECK DETAILS ~~~~~~~~~~~~~~~~~~~~~ //
$arrCCeCheckInfo = $objAuthorizeAPI->getCCeCheckInfo(1501067535,1500625311);


// ~~~~~~~~~~~~~~~~~~~~~~~~ REFUND MONEY ~~~~~~~~~~~~~~~~~~~~~ //
$arrRefundResponse = $objAuthorizeAPI->refundMoneyFromTransaction(1501067535,1500625311, '6547898132', 20.12);


// ~~~~~~~~~~~~~~~~~~~~~~~~ GET TRANSACTION STATUS ~~~~~~~~~~~~~~~~~~~~~ //
$arrTransactionStatus = $objAuthorizeAPI->getTransactionStatus('6547898132');


// ~~~~~~~~~~~~~~~~ DELETE CUSTOMER PAYMENT PROFILE / DELETE CREDIT CARD / ECHECK INFO ~~~~~~~~~~~ //
$arrCPPDeleteResponse = $objAuthorizeAPI->deleteCustomerPaymentProfile(1501067535,1500625311);


// ~~~~~~~~~~~~~~~~~~~~~~~~ DELETE CUSTOMER ~~~~~~~~~~~~~~~~~~~~~ //
$arrCPDeleteResponse = $objAuthorizeAPI->deleteCustomerProfile(1501067535);


?>