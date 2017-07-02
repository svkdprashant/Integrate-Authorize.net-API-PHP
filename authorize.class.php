<?php
/*
 ----------------------------------------------------------------
 Author: Prashant Jethwa
 Purpose: Create class for Authorize.net payment gateway api.
 Email: codebucket.co@gmail.com
 Last Modified on 19 May 2017
 -----------------------------------------------------------------
 
 Useful Functions in this class
 --------------------------------
 1. addCustomerPaymentProfile
    -> To create customer payment profile in authorize.net
    -> We are checking if customer profile exist or not. If customer profile is not exists then create it and then create customer payment profile.
	-> Parameters:
		1. Customer id: Unique id of customer
		2. Payment Type: cc / echeck (Credit Card / eCheck)
		3. Exist customer Profile (true/false): If customer is already synced and it's entry exists in your table then pass true. If you will pass true then 4th parameter is mandatory
		4. Customer Profile Id: Authorize.net profile id of customer. It is mandatory if you pass true for 3rd parameter

 2. deleteCustomerProfile
    -> To delete customer profile from authorize.net
	-> Parameters:
		1. Customer id: Unique id of customer
 
 3. deleteCustomerPaymentProfile
    -> To delete Credit card / eCheck from authorize.net
	-> Parameters:
		1. Customer Profile id: Customer's Profile id of authorize.net
		2. Customer Payment Profile id: Payment profile id of authorize.net (You will have unique payment profile id for each Credit card / eCheck)
 
 4. getCCeCheckInfo
    -> get Credit card/echeck information from authorize.net
	-> Parameters:
		1. Customer Profile id: Customer's Profile id of authorize.net
		2. Customer Payment Profile id: Payment profile id of authorize.net
 
 5. chargeCCeCheck
   	-> Charge customer's credit card / eCheck
	-> Parameters:
		1. Customer Profile id: Customer's Profile id of authorize.net
		2. Customer Payment Profile id: Payment profile id of authorize.net
		3. Amount: Amount you want to charge to the customer
 
 6. directChargeCCeCheck
	-> Charge Directly from customer's credit card / eCheck (Without payment profile)
	-> Parameters:
		1. Payment method (cc / echeck)
		2. Amount
 
 7. refundMoneyFromTransaction
   	-> To refund amount from transaction. It requires transaction id.
	-> Parameters:
		1. Customer Profile id: Customer's Profile id of authorize.net
		2. Customer Payment Profile id: Payment profile id of authorize.net
		3. Transaction id: Transaction id of a payment
		4. Amount: Amount you want to refund to the customer

8. voidTransaction
	-> To void any transaction
	-> Parameters:
		1. Customer Profile id: Customer's Profile id of authorize.net
		2. Customer Payment Profile id: Payment profile id of authorize.net
		3. Transaction id: Transaction id of a payment

9. getTransactionStatus
   	-> To get Transaction status
   	-> Parameters:
   		1. Transaction id

10. setCreditCardParameters
    -> It will set credit card parameters in this class. This information will be used while creating customer payment profile in authorize.net
    -> Parameters:
    	1. Credit Card number
    	2. Credit Card Expiration Date (YYYY-MM)
    	3. CVV Number

11. setBankParameters 
    -> It will set echeck parameters for this class. This information will be used while creating customer payment profile in authorize.net
    -> Parameters: 
	  	1. Routing Number
	  	2. Account Number
	  	3. Name on Account
	  	4. Account Type (checking, savings, or businessChecking)
	  	5. eCheck Type. The type of electronic check payment request. (ARC, BOC, CCD, PPD, TEL, WEB). It is required for directChargeCCeCheck

12. setCustomerAddress
    -> It will set customer's address information in this class's variables.
    -> Parameters: 
    	1. Array of Customer Information.

*/

class AuthorizeAPI
{
	private $strAPILoginId 		= '';
	private $strTransactionKey 	= '';
	private $strValidationMode = '';
	
	private $strCreditCardNumber 	= '';
	private $strCreditCardExpDate 	= '';
	private $strCardCode = '';
	
	private $strAccountType 	= '';
	private $strRoutingNumber 	= '';
	private $strAccountNumber 	= '';
	private $strNameOnAccount 	= '';
	private $strEcheckType 		= '';
	
	private $strPostURL = '';
    private $strChargeByAmountURL = '';
    private $blnTestMode = true;
    private $arrCustomerAddress = array();

    /*
		-> Set Authorize LoginId, transactionKey and Mode (testMode, liveMode)
		-> Parameters:
			1. strAPILoginId: API Login Id of authorize.net
			2. strTransactionKey: Transaction Key of authorize.net
			3. Site Mode: testMode / liveMode
    */
    function __construct($strAPILoginId, $strTransactionKey, $strValidationMode = 'testMode')
    {
    	$this->strAPILoginId 		= $strAPILoginId;
		$this->strTransactionKey 	= $strTransactionKey;
		$this->strValidationMode 	= $strValidationMode;
	  
        if($this->strValidationMode == 'liveMode')
		{
			$this->strPostURL = 'https://api.authorize.net/xml/v1/request.api';
			$this->strChargeByAmountURL = 'https://secure.authorize.net/gateway/transact.dll';
            if($this->strValidationMode == 'liveMode') { $this->blnTestMode = false ;}
		}
		else
		{
			$this->strPostURL = 'https://apitest.authorize.net/xml/v1/request.api';
			$this->strChargeByAmountURL = 'https://test.authorize.net/gateway/transact.dll';
        }
    }

    /*
     1. addCustomerPaymentProfile
	    -> To create customer payment profile in authorize.net
	    -> We are checking if customer profile exist or not. If customer profile is not exists then create it and then create customer payment profile.
		-> Parameters:
			1. Customer id: Unique id of customer
			2. Payment Type: cc / echeck (Credit Card / eCheck)
			3. Exist customer Profile (true/false): If customer is already synced and it's entry exists in your table then pass true. If you will pass true then 4th parameter is mandatory
			4. Customer Profile Id: Authorize.net profile id of customer. It is mandatory if you pass true for 3rd parameter
     */
	function addCustomerPaymentProfile($intCustomerId, $strPaymentType, $blnExistCustomerProfile = false,$strCustomerProfileId = '')
	{
		$arrCustomerProfile = array();
        
        if($blnExistCustomerProfile)
		{
            $arrCustomerProfile['message'] = 'success';
            $arrCustomerProfile['customerProfileId'] = $strCustomerProfileId;
		}
		else
		{
			$arrCustomerProfile = $this->createCustomerProfile($intCustomerId);
		}

    	$strFinalResult['success'] = '';
		$strFinalResult['customerProfileId'] = '';
		$strFinalResult['customerPaymentProfileId'] = '';
		$strFinalResult['error'] = '';
		
		if($arrCustomerProfile['message'] == 'success')
		{
			$strCustomerProfileID = $arrCustomerProfile['customerProfileId'];
			
			$arrCustomerPaymentProfile = array();
			$arrCustomerPaymentProfile = $this->createCustomerPaymentProfile($strCustomerProfileID, $strPaymentType);
			if($arrCustomerPaymentProfile['message'] == 'success')
			{
				$strCustomerPaymentProfileID = $arrCustomerPaymentProfile['customerPaymentProfileId'];

				$strFinalResult['success'] = '1';
				$strFinalResult['customerProfileId'] = $strCustomerProfileID;
				$strFinalResult['customerPaymentProfileId'] = $strCustomerPaymentProfileID;
				$strFinalResult['error'] = '';
				$strFinalResult['paymentFlag'] = '1';
				$strFinalResult['message'] = '';
			}
			else
			{
				if(!$blnExistCustomerProfile)
				{
                    $arrDelCustomerProfile = $this->deleteCustomerProfile($strCustomerProfileID);
				}
				
				$strFinalResult['success'] = '0';
				$strFinalResult['error'] = $arrCustomerPaymentProfile['message'];
				$strFinalResult['paymentFlag'] = '0';
				$strFinalResult['message'] = $arrCustomerPaymentProfile['message'];
				$strFinalResult['errorCode'] = $arrCustomerPaymentProfile['errorCode'];
			}
			
		}
		else
		{
			$strFinalResult['success'] = '0';
			$strFinalResult['error'] = $arrCustomerProfile['message'];
			$strFinalResult['paymentFlag'] = '0';
			$strFinalResult['message'] = $arrCustomerProfile['message'];
			$strFinalResult['errorCode'] = $arrCustomerProfile['errorCode'];
		}

        return json_encode($strFinalResult);
        exit();
	}
	

	function createCustomerProfile($intCustomerId)
	{
		$strXML = '<?xml version="1.0" encoding="utf-8"?>
					<createCustomerProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
					<merchantAuthentication>
						<name>'.$this->strAPILoginId.'</name>
						<transactionKey>'.$this->strTransactionKey.'</transactionKey>
					</merchantAuthentication>
					<profile>
					<merchantCustomerId>'.$intCustomerId.'</merchantCustomerId>
					<email>'.$this->arrCustomerAddress['em_email'].'</email>
					</profile>
					</createCustomerProfileRequest>';

		$arrCreateProfileResponse = $this->curlPost($strXML);
		
		$arrCreateCustomerProfileResponse = array();
		if($arrCreateProfileResponse['messages']['resultCode'] == 'Ok')
		{
			$arrCreateCustomerProfileResponse['customerProfileId'] = $arrCreateProfileResponse['customerProfileId'];
			$arrCreateCustomerProfileResponse['message'] = 'success';
		}
		else
		{
			$arrCreateCustomerProfileResponse['customerProfileId'] = '';
			$arrCreateCustomerProfileResponse['message'] = $arrCreateProfileResponse['messages']['message']['text'];
			$arrCreateCustomerProfileResponse['errorCode'] = $arrCreateProfileResponse['messages']['message']['code'];
		}
		
		return $arrCreateCustomerProfileResponse;
	}

	function createCustomerPaymentProfile($strCustomerProfileID, $strPaymentType)
	{
		if($strPaymentType == 'cc')
		{
			$strCustomerPaymentProfileXML = $this->generateCreditCardXML($strCustomerProfileID);
        }
		else if($strPaymentType == 'echeck')
		{
			$strCustomerPaymentProfileXML = $this->generateECheckXML($strCustomerProfileID);
		}

		$arrCreateCustomerPaymentProfileResponseFromPost = $this->curlPost($strCustomerPaymentProfileXML);
        
        if($arrCreateCustomerPaymentProfileResponseFromPost['messages']['resultCode'] == 'Ok')
		{
			$arrCreateCustomerPaymentProfileResponse['customerPaymentProfileId'] = $arrCreateCustomerPaymentProfileResponseFromPost['customerPaymentProfileId'];
			$arrCreateCustomerPaymentProfileResponse['message'] = 'success';
		}
		else
		{
            if(empty($arrCreateCustomerPaymentProfileResponseFromPost['messages']['message']['text']) && !empty($arrCreateCustomerPaymentProfileResponseFromPost['messages']['message'][0]['text']))
            {
                $arrCreateCustomerPaymentProfileResponseFromPost['messages']['message']['text'] = $arrCreateCustomerPaymentProfileResponseFromPost['messages']['message'][0]['text'];
            }

			$arrCreateCustomerPaymentProfileResponse['customerPaymentProfileId'] = '';
			$arrCreateCustomerPaymentProfileResponse['message'] = $arrCreateCustomerPaymentProfileResponseFromPost['messages']['message']['text'];
			$arrCreateCustomerPaymentProfileResponse['errorCode'] = $arrCreateCustomerPaymentProfileResponseFromPost['messages']['message']['code'];
		}
		
		return $arrCreateCustomerPaymentProfileResponse;
	}
	
	/*
	  2. deleteCustomerProfile
	    -> To delete customer profile from authorize.net
		-> Parameters:
			1. Customer id: Unique id of customer
	*/
	function deleteCustomerProfile($strCustomerProfileId)
	{
		$strXML = '<?xml version="1.0" encoding="utf-8"?>
					<deleteCustomerProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
					  <merchantAuthentication>
						<name>'.$this->strAPILoginId.'</name>
						<transactionKey>'.$this->strTransactionKey.'</transactionKey>
					  </merchantAuthentication>
					  <customerProfileId>'.$strCustomerProfileId.'</customerProfileId>
					</deleteCustomerProfileRequest>';
		
		$arrDeleteProfile = $this->curlPost($strXML);
		
		$arrCustomerProfileResponse = array();
		
		if($arrDeleteProfile['messages']['resultCode'] == 'Ok')
		{
			$arrCustomerProfileResponse['message'] = 'success';
		}
		else
		{
			$arrCustomerProfileResponse['message'] = $arrDeleteProfile['messages']['message']['text'];
		}
		
		return $arrCustomerProfileResponse;
	}
	
	/*
	  3. deleteCustomerPaymentProfile
	    -> To delete Credit card / eCheck from authorize.net
		-> Parameters:
			1. Customer Profile id: Customer's Profile id of authorize.net
			2. Customer Payment Profile id: Payment profile id of authorize.net (You will have unique payment profile id for each Credit card / eCheck)
	*/
	function deleteCustomerPaymentProfile($strCustomerProfileID, $strCustomerPaymentProfileId)
	{
		$strXML = '<?xml version="1.0" encoding="utf-8"?>
					<deleteCustomerPaymentProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
						<merchantAuthentication>
							<name>'.$this->strAPILoginId.'</name>
							<transactionKey>'.$this->strTransactionKey.'</transactionKey>
						</merchantAuthentication>
						<customerProfileId>'.$strCustomerProfileID.'</customerProfileId>
						<customerPaymentProfileId>'.$strCustomerPaymentProfileId.'</customerPaymentProfileId>
					</deleteCustomerPaymentProfileRequest>';
				
		$arrDeleteCustomerPaymentProfile = $this->curlPost($strXML);

		$arrDeleteCPPResponse = array();
		if($arrDeleteCustomerPaymentProfile['messages']['resultCode'] == 'Ok')
		{
			$arrDeleteCPPResponse['success'] = '1';
			$arrDeleteCPPResponse['error'] = '';
			return json_encode($arrDeleteCPPResponse);
			exit();
		}
		else
		{
			$arrDeleteCPPResponse['success'] = '0';
			$arrDeleteCPPResponse['error'] = $arrDeleteCustomerPaymentProfile['messages']['message']['text'];
			return json_encode($arrDeleteCPPResponse);
			exit();
		}
	}
	
	/*
	  4. getCCeCheckInfo
	    -> get Credit card/echeck information from authorize.net
		-> Parameters:
			1. Customer Profile id: Customer's Profile id of authorize.net
			2. Customer Payment Profile id: Payment profile id of authorize.net
	*/
	function getCCeCheckInfo($strCustomerProfileID, $strCustomerPaymentProfileId)
	{
		$strXML =  '<?xml version="1.0" encoding="utf-8"?>
					<getCustomerPaymentProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
						<merchantAuthentication>
							<name>'.$this->strAPILoginId.'</name>
							<transactionKey>'.$this->strTransactionKey.'</transactionKey>
						</merchantAuthentication>
					<customerProfileId>'.$strCustomerProfileID.'</customerProfileId>
					<customerPaymentProfileId>'.$strCustomerPaymentProfileId.'</customerPaymentProfileId>
					</getCustomerPaymentProfileRequest>';
					
		$arrGetCCCheck = $this->curlPost($strXML);
		
		$arrGetCCCheckResponse = array();
		
		if($arrGetCCCheck['messages']['resultCode'] == 'Ok')
		{
			$arrGetCCCheckResponse['success'] 					= '1';
			$arrGetCCCheckResponse['customerPaymentProfileId'] 	= $arrGetCCCheck['paymentProfile']['customerPaymentProfileId'];

            if(!empty($arrGetCCCheck['paymentProfile']['payment']['creditCard']['cardNumber']))
            {
                $arrGetCCCheckResponse['paymentType'] = 'cc';
                $arrGetCCCheckResponse['cardNumber'] 				= $arrGetCCCheck['paymentProfile']['payment']['creditCard']['cardNumber'];
                $arrGetCCCheckResponse['expirationDate'] 			= $arrGetCCCheck['paymentProfile']['payment']['creditCard']['expirationDate'];
                $arrGetCCCheckResponse['cardCode']                  = $arrGetCCCheck['paymentProfile']['payment']['creditCard']['cardCode'];
            }
            else
            {
                $arrGetCCCheckResponse['paymentType'] = 'echeck';
                $arrGetCCCheckResponse['accountType']                  = $arrGetCCCheck['paymentProfile']['payment']['bankAccount']['accountType'];
                $arrGetCCCheckResponse['routingNumber']                = $arrGetCCCheck['paymentProfile']['payment']['bankAccount']['routingNumber'];
                $arrGetCCCheckResponse['accountNumber']                = $arrGetCCCheck['paymentProfile']['payment']['bankAccount']['accountNumber'];
                $arrGetCCCheckResponse['nameOnAccount']                = $arrGetCCCheck['paymentProfile']['payment']['bankAccount']['nameOnAccount'];
            }

            $arrGetCCCheckResponse['error'] = '';
		}
		else
		{
			$arrGetCCCheckResponse['success'] = '0';
			$arrGetCCCheckResponse['error'] = $arrGetCCCheck['messages']['message']['text'];
		}
        
        return json_encode($arrGetCCCheckResponse);
		exit();
	}

	/*
	  5. chargeCCeCheck
	   	-> Charge customer's credit card / eCheck
		-> Parameters:
			1. Customer Profile id: Customer's Profile id of authorize.net
			2. Customer Payment Profile id: Payment profile id of authorize.net
			3. Amount: Amount you want to charge to the customer
	*/
	function chargeCCeCheck($strCustomerProfileID, $strCustomerPaymentProfileId, $fltAmount)
	{
		$strXML = '<?xml version="1.0" encoding="utf-8"?>
					<createCustomerProfileTransactionRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
						<merchantAuthentication>
							<name>'.$this->strAPILoginId.'</name>
							<transactionKey>'.$this->strTransactionKey.'</transactionKey>
						</merchantAuthentication>
					<transaction>
						<profileTransAuthCapture>
							<amount>'.$fltAmount.'</amount>
							<customerProfileId>'.$strCustomerProfileID.'</customerProfileId>
							<customerPaymentProfileId>'.$strCustomerPaymentProfileId.'</customerPaymentProfileId>
						</profileTransAuthCapture>
					</transaction>
					</createCustomerProfileTransactionRequest>';
					
		$arrChargeCCCheck = $this->curlPost($strXML);
		
		$arrChargeCCCheckResponse = array();
		$arrChargeCCCheckResponse['type'] 		= '';
		
		if($arrChargeCCCheck['messages']['resultCode'] == 'Ok')
		{
			$strAuthorizeDirectResponse = $arrChargeCCCheck['directResponse'];
            $arrDirectResponse = explode(",", $strAuthorizeDirectResponse);

			$arrChargeCCCheckResponse['success']        = '1';
			$arrChargeCCCheckResponse['error']          = '';
            $arrChargeCCCheckResponse['paymentFlag'] 	= '1';
			$arrChargeCCCheckResponse['message'] 		= '';
			$arrChargeCCCheckResponse['transId'] 		= $arrDirectResponse[6];
			$arrChargeCCCheckResponse['type'] 			= $arrDirectResponse[51];
			
		}
		else
		{
			$arrChargeCCCheckResponse['success']        = '0';
			$arrChargeCCCheckResponse['error']          = $arrChargeCCCheck['messages']['message']['text'];
            $arrChargeCCCheckResponse['paymentFlag']    = '0';
			$arrChargeCCCheckResponse['message']        = $arrChargeCCCheck['messages']['message']['text'];
            $arrChargeCCCheckResponse['transId'] 		= '';
		}

        return json_encode($arrChargeCCCheckResponse);
        exit();
	}

	/*
	 6. directChargeCCeCheck
		-> Charge Directly from customer's credit card / eCheck (Without payment profile)
		-> Parameters:
			1. Payment method (cc / echeck)
			2. Amount
	*/
    function directChargeCCeCheck($paymentMethod,$amount)
    {
        if($paymentMethod == "cc")
        {
            $post_values = array(
                "x_login" => $this->strAPILoginId,
                "x_tran_key" => $this->strTransactionKey,
                "x_version" => "3.1",
                "x_delim_data" => "TRUE",
                "x_delim_char" => "|",
                "x_relay_response" => "FALSE",
                "x_test_request" => $this->blnTestMode,
                "x_type" => "AUTH_CAPTURE",
                "x_method" => "CC",
                "x_card_num" => $this->strCreditCardNumber,
                "x_exp_date" => $this->strCreditCardExpDate,
                "x_card_code" => $this->strCardCode,
                "x_amount" => $amount
            );
        }
        else
        {
            $post_values = array(
                "x_login" => $this->strAPILoginId,
                "x_tran_key" => $this->strTransactionKey,
                "x_version" => "3.1",
                "x_delim_data" => "TRUE",
                "x_delim_char" => "|",
                "x_relay_response" => "FALSE",
                "x_test_request" => $this->blnTestMode,
                "x_method"      => "ECHECK",
                "x_bank_aba_code"	=> $this->strRoutingNumber,
                "x_bank_acct_num"	=> $this->strAccountNumber,
                "x_bank_acct_type"	=> $this->strAccountType,
                "x_bank_acct_name"	=> $this->strNameOnAccount,
                "x_echeck_type"	=> $this->strEcheckType,
                "x_bank_check_number"   => "FALSE",
                "x_amount"	=> $amount
                );
        }

        $post_string = "";

        foreach ($post_values as $key => $value)
        {
            $post_string .= "$key=" . urlencode($value) . "&";
        }

        $post_string = rtrim($post_string, "& ");

        $request = curl_init($this->strChargeByAmountURL); // initiate curl object
        curl_setopt($request, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
        curl_setopt($request, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
        curl_setopt($request, CURLOPT_POSTFIELDS, $post_string); // use HTTP POST to send form data
        curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response.
        $post_response = curl_exec($request); // execute curl post and store results in $post_response
        curl_close($request); // close curl object

        $response_array = explode($post_values["x_delim_char"], $post_response);

        $arrChargeCCEheckResponse = array();

        if($response_array[0] == 1)
        {
            $arrChargeCCEheckResponse['paymentFlag'] = 1;
            $arrChargeCCEheckResponse['message'] = '';
            $arrChargeCCEheckResponse['transId'] = $response_array[6];
        }
        else
        {
            $arrChargeCCEheckResponse['paymentFlag'] = 0;
            $arrChargeCCEheckResponse['message'] = $response_array[3];
            $arrChargeCCEheckResponse['transId'] = '';
        }

        return $arrChargeCCEheckResponse;
        exit();
    }

    /*
     7. refundMoneyFromTransaction
	   	-> To refund amount from transaction. It requires transaction id.
		-> Parameters:
			1. Customer Profile id: Customer's Profile id of authorize.net
			2. Customer Payment Profile id: Payment profile id of authorize.net
			3. Transaction id: Transaction id of a payment
			4. Amount: Amount you want to refund to the customer
    */
    function refundMoneyFromTransaction($strCustomerProfileID, $strCustomerPaymentProfileId, $strTransactionId, $fltAmount)
    {
        $strTransactionStatus = $this->getTransactionStatus($strTransactionId);
        $arrStatus = json_decode($strTransactionStatus, TRUE);
        
        $fltAmount = number_format((float)$fltAmount, 2, '.', '');
        
        if($arrStatus['success'] == 1)
        {
            if($arrStatus['transactionStatus'] == 'settledSuccessfully')
            {
                return $this->refundTransaction($strCustomerProfileID, $strCustomerPaymentProfileId, $strTransactionId, $fltAmount);
            }
            else
            {
                if($arrStatus['settleAmount'] == $fltAmount)
                {
                    return $this->voidTransaction($strCustomerProfileID, $strCustomerPaymentProfileId, $strTransactionId);
                }
                else
                {
                	$strVoidTransaction = $this->voidTransaction($strCustomerProfileID, $strCustomerPaymentProfileId, $strTransactionId);
                	$arrVoidTransaction = json_decode($strVoidTransaction, TRUE);

                	if($arrVoidTransaction['success'] == 1)
                	{
                		$fltDifferenceAmt = $arrStatus['settleAmount'] - $fltAmount;
                		return $this->chargeCCeCheck($strCustomerProfileID, $strCustomerPaymentProfileId, $fltDifferenceAmt);
                	}
                	else
                	{
                		return $strVoidTransaction;
                	}
                }
            }
        }
        else
        {
            return $strTransactionStatus;
            exit();
        }
    }
    
    function refundTransaction($strCustomerProfileID, $strCustomerPaymentProfileId, $strTransactionId, $fltAmount)
    {
        $strXML = '<?xml version="1.0" encoding="utf-8"?>
                    <createCustomerProfileTransactionRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
                        <merchantAuthentication>
                            <name>'.$this->strAPILoginId.'</name>
							<transactionKey>'.$this->strTransactionKey.'</transactionKey>
                        </merchantAuthentication>
                        <transaction>
                            <profileTransRefund>
                                <amount>'.$fltAmount.'</amount>
                                <customerProfileId>'.$strCustomerProfileID.'</customerProfileId>
                                <customerPaymentProfileId>'.$strCustomerPaymentProfileId.'</customerPaymentProfileId>
                                <transId>'.$strTransactionId.'</transId>
                            </profileTransRefund>
                        </transaction>
                    </createCustomerProfileTransactionRequest>';
        
        $arrRefund = $this->curlPost($strXML);
        
		$arrRefundResponse = array();

		if($arrRefund['messages']['resultCode'] == 'Ok')
		{
			$arrRefundResponse['success'] 	= '1';
			$arrRefundResponse['error'] 		= '';
			
		}
		else
		{
			$arrRefundResponse['success'] = '0';
			$arrRefundResponse['error'] = $arrRefund['messages']['message']['text'];
		}

        return json_encode($arrRefundResponse);
		exit();
    }
	
	/*
	  8. voidTransaction
		-> To void any transaction
		-> Parameters:
			1. Customer Profile id: Customer's Profile id of authorize.net
			2. Customer Payment Profile id: Payment profile id of authorize.net
			3. Transaction id: Transaction id of a payment
	*/
    function voidTransaction($strCustomerProfileID, $strCustomerPaymentProfileId, $strTransactionId)
    {
        $strXML = '<?xml version="1.0" encoding="utf-8"?>
                    <createCustomerProfileTransactionRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
                        <merchantAuthentication>
                            <name>'.$this->strAPILoginId.'</name>
							<transactionKey>'.$this->strTransactionKey.'</transactionKey>
                        </merchantAuthentication>
                        <transaction>
                            <profileTransVoid>
                                <customerProfileId>'.$strCustomerProfileID.'</customerProfileId>
                                <customerPaymentProfileId>'.$strCustomerPaymentProfileId.'</customerPaymentProfileId>
                                <transId>'.$strTransactionId.'</transId>
                            </profileTransVoid>
                        </transaction>
                    </createCustomerProfileTransactionRequest>';
        
        $arrVoid = $this->curlPost($strXML);

		$arrVoidResponse = array();

	

		if($arrVoid['messages']['resultCode'] == 'Ok')
		{
			$arrVoidResponse['success'] 	= '1';
			$arrVoidResponse['error'] 		= '';
			$arrVoidResponse['response'] 	= $arrVoid;
			return json_encode($arrVoidResponse);
			exit();
		}
		else
		{
			$arrVoidResponse['success'] = '0';
			$arrVoidResponse['error'] = $arrVoid['messages']['message']['text'];
			return json_encode($arrVoidResponse);
			exit();
		}
    }
    
    /*
	  9. getTransactionStatus
	   	-> To get Transaction status
	   	-> Parameters:
	   		1. Transaction id
    */
    function getTransactionStatus($strTransactionId)
    {
        $strXML = '<?xml version="1.0" encoding="utf-8"?>
					<getTransactionDetailsRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
						<merchantAuthentication>
							<name>'.$this->strAPILoginId.'</name>
							<transactionKey>'.$this->strTransactionKey.'</transactionKey>
						</merchantAuthentication>
						<transId>'.$strTransactionId.'</transId>
					</getTransactionDetailsRequest>';
					
		$arrTransactionStatus = $this->curlPost($strXML);
        
        $arrTransactionStatusResponse = array();

		if($arrTransactionStatus['messages']['resultCode'] == 'Ok')
		{
			$arrTransactionStatusResponse['success'] 	= '1';
			$arrTransactionStatusResponse['error'] 		= '';
			$arrTransactionStatusResponse['transactionStatus'] 		= $arrTransactionStatus['transaction']['transactionStatus'];
			$arrTransactionStatusResponse['settleAmount'] 		= $arrTransactionStatus['transaction']['settleAmount'];
			return json_encode($arrTransactionStatusResponse);
			exit();
		}
		else
		{
			$arrTransactionStatusResponse['success'] = '0';
			$arrTransactionStatusResponse['error'] = $arrTransactionStatus['messages']['message']['text'];
            $arrTransactionStatusResponse['transactionStatus'] = '';
            $arrTransactionStatusResponse['settleAmount'] = '';
			return json_encode($arrTransactionStatusResponse);
			exit();
		}
    }
	
	/*
	 10. setCreditCardParameters
	    -> It will set credit card parameters in this class. This information will be used while creating customer payment profile in authorize.net
	    -> Parameters:
	    	1. Credit Card number
	    	2. Credit Card Expiration Date (YYYY-MM)
	    	3. CVV Number
	*/
	function setCreditCardParameters($strCreditCardNumber, $strCreditCardExpDate, $strCVV = '')
	{
		$this->strCreditCardNumber 	= $strCreditCardNumber;
		$this->strCreditCardExpDate = $strCreditCardExpDate;
		$this->strCardCode = $strCVV;
	}
	
	/*
	 11. setBankParameters 
	    -> It will set echeck parameters for this class. This information will be used while creating customer payment profile in authorize.net
	    -> Parameters: 
		  	1. Routing Number
		  	2. Account Number
		  	3. Name on Account
		  	4. Account Type (checking, savings, or businessChecking)
		  	5. eCheck Type. The type of electronic check payment request. (ARC, BOC, CCD, PPD, TEL, WEB). It is required for directChargeCCeCheck
	*/
	function setBankParameters($strRoutingNumber, $strAccountNumber, $strNameOnAccount, $strAccountType , $strEcheckType = '')
	{
		$this->strAccountType 	= $strAccountType;
		$this->strRoutingNumber = $strRoutingNumber;
		$this->strAccountNumber = $strAccountNumber;
		$this->strNameOnAccount = $strNameOnAccount;
		$this->strEcheckType 	= $strEcheckType;
	}
	
	function curlPost($strContent, $strURL = '')
	{
		if($strURL == '')
		{
			$strURL = $this->strPostURL;
		}
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $strURL);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml"));
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $strContent);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		$strResponse = curl_exec($ch);
		
		
		$intPOS = strripos($strResponse,"<?xml");
		$strResponseXML = substr($strResponse,$intPOS);
		
		$strResponseReturn = simplexml_load_string($strResponseXML, "SimpleXMLElement", LIBXML_NOWARNING);
		
		$arrResponseReturn = json_decode(json_encode($strResponseReturn), TRUE);
		
		return $arrResponseReturn;
	}
	
	function generateCreditCardXML($strCustomerProfileID, $arrCustomerAddress)
	{
        $strCardCodeXML = '';
        $strBillXML = '';

        if(!empty($this->strCardCode))
        {
            $strCardCodeXML = '<cardCode>'.$this->strCardCode.'</cardCode>';
        }
        
        $strBillXML = $this->getBillAddressXML();
        
		$strXML = 	'<?xml version="1.0" encoding="utf-8"?>
					<createCustomerPaymentProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
						<merchantAuthentication>
							<name>'.$this->strAPILoginId.'</name>
							<transactionKey>'.$this->strTransactionKey.'</transactionKey>
						</merchantAuthentication>
						<customerProfileId>'.$strCustomerProfileID.'</customerProfileId>
						<paymentProfile>
                                                        '.$strBillXML.'
							<payment>
								<creditCard>
									<cardNumber>'.$this->strCreditCardNumber.'</cardNumber>
									<expirationDate>'.$this->strCreditCardExpDate.'</expirationDate>
									'.$strCardCodeXML.'
								</creditCard>
							</payment>
						</paymentProfile>
					<validationMode>'.$this->strValidationMode.'</validationMode>
					</createCustomerPaymentProfileRequest>';
		return $strXML;
	}
	
	function generateECheckXML($strCustomerProfileID)
	{
        $strBillXML = '';
        $strBillXML = $this->getBillAddressXML();
        
        $strXML = '<?xml version="1.0" encoding="utf-8"?>
					<createCustomerPaymentProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
						<merchantAuthentication>
							<name>'.$this->strAPILoginId.'</name>
							<transactionKey>'.$this->strTransactionKey.'</transactionKey>
						</merchantAuthentication>
						<customerProfileId>'.$strCustomerProfileID.'</customerProfileId>
						<paymentProfile>
                        '.$strBillXML.'
							<payment>
								<bankAccount>
									<accountType>'.$this->strAccountType.'</accountType>
									<routingNumber>'.$this->strRoutingNumber.'</routingNumber>
									<accountNumber>'.$this->strAccountNumber.'</accountNumber>
									<nameOnAccount>'.$this->strNameOnAccount.'</nameOnAccount>
								</bankAccount>
							</payment>
						</paymentProfile>
					<validationMode>'.$this->strValidationMode.'</validationMode>
					</createCustomerPaymentProfileRequest>';
		
		return $strXML;
	}
    
    /*
	  12. setCustomerAddress
	    -> It will set customer's address information in this class's variables.
	    -> Parameters: 
	    	1. Array of Customer Information.
    */
    function setCustomerAddress($arrAddress)
    {
        $this->arrCustomerAddress = array();
        $this->arrCustomerAddress['firstname']    	= $arrAddress['firstname'];
        $this->arrCustomerAddress['lastname']    	= $arrAddress['lastname'];
        $this->arrCustomerAddress['company_name']   = $arrAddress['company_name'];
        $this->arrCustomerAddress['address']    	= $arrAddress['ad_street'];
        $this->arrCustomerAddress['address1']   	= $arrAddress['ad_street1'];
        $this->arrCustomerAddress['city']       	= $arrAddress['ad_city'];
        $this->arrCustomerAddress['state']      	= $arrAddress['ad_state'];
        $this->arrCustomerAddress['zip']        	= $arrAddress['ad_zip'];
        $this->arrCustomerAddress['country']    	= $arrAddress['ad_country'];
        $this->arrCustomerAddress['ph_number']    	= $arrAddress['ph_number'];
        $this->arrCustomerAddress['em_email']    	= $arrAddress['em_email'];
    }
    
    function getBillAddressXML()
    {
        $blnUsedCityAsAddress = 0;
        
        $strXML = '';
        $strXML .= '<billTo>';
        
        $strXML .= '<firstName>'.$this->arrCustomerAddress['firstname'].'</firstName>';
        $strXML .= '<lastName>'.$this->arrCustomerAddress['lastname'].'</lastName>';
        //$strXML .= '<company>'.$this->arrCustomerAddress['company_name'].'</company>';
		$strXML .= '<company><![CDATA['.$this->arrCustomerAddress['company_name'].']]></company>';

        if($this->arrCustomerAddress['address'] != "")
        {
            $strXML .= '<address>'.$this->arrCustomerAddress['address'].'</address>';
        }
        else
        {
            $strXML .= '<address>'.$this->arrCustomerAddress['city'].'</address>';
            $blnUsedCityAsAddress = 1;
        }
        
        if(!$blnUsedCityAsAddress)
        {
            $strXML .= '<city>'.$this->arrCustomerAddress['city'].'</city>';
        }
        
        $strXML .= '<state>'.$this->arrCustomerAddress['state'].'</state>';
        $strXML .= '<zip>'.$this->arrCustomerAddress['zip'].'</zip>';
        $strXML .= '<country>'.$this->arrCustomerAddress['country'].'</country>';
        $strXML .= '<phoneNumber>'.$this->arrCustomerAddress['ph_number'].'</phoneNumber>';
        $strXML .= '</billTo>';
        
        return $strXML;
    }
}


?>
