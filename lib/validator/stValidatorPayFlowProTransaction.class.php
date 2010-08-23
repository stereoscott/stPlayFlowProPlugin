<?php

class stValidatorPayFlowProTransaction extends sfValidatorSchema
{
  protected 
    $payFlowProForm = null,
    $responseArray = null,
    $testMode = true;
  
  public function __construct(PayFlowProForm $form, $options = array(), $messages = array())
  {
    $this->payFlowProForm = $form;
    
    $this->setTestMode(sfConfig::get('app_payflowpro_env') == 'Live' ? false : true);
    
    $this->addMessage('invalid', 'General Error.  Please contact Customer Support.');
    $this->addMessage('account_config', 'General Error. Please contact Customer Support.'); //Account configuration issue, verify login credentials.
    $this->addMessage('avsaddr', 'Your billing (street) information does not match. Please re-enter.');
    $this->addMessage('avszip', 'Your billing (zip) information does not match. Please re-enter.');
    $this->addMessage('cvv2match', 'Your billing (cvv2) information does not match. Please re-enter.');
    $this->addMessage('declined', 'Your transaction was declined.');
    $this->addMessage('voice_authorization', 'Your Transaction is pending. Contact Customer Service to complete your order.');
    $this->addMessage('invalid_cc', 'Invalid credit card information. Please re-enter.');
    $this->addMessage('fraud_125', 'Your transaction has been declined. Contact Customer Service to place your order.');
    $this->addMessage('fraud_126', 'Your transaction is under review. We will notify you via e-mail if accepted.');
    $this->addMessage('fraud_127', 'Your transaction is under review. We will notify you via e-mail if accepted.');
    $this->addMessage('no_response', 'There was no response from our credit card processor.');
    
    if (sfConfig::get('app_payflowpro_show_respmsg')) {
      foreach ($this->getMessages() as $code => $message) {
        $this->setMessage($code, $message);
      }
    }

    parent::__construct(null, $options, $messages);
  }
  
  public function getTestMode() 
  {
    return $this->testMode;
  }

  public function setTestMode($v) 
  {
    $this->testMode = $v;
  }

  public function getSubmitUrl()
  {
    return $this->getTestMode() ? sfConfig::get('app_payflowpro_url_test') : sfConfig::get('app_payflowpro_url_live');
  }
  
  public function isValidTransaction($value = null)
  {
    return $this->payFlowProForm->isValidTransaction($value);
  }
  
  public function getResponseArray()
  {
    return $this->responseArray;
  }
  
  protected function doClean($values)
  {
    
    if (is_null($values))
    {
      $values = array();
    }

    if (!is_array($values))
    {
      throw new InvalidArgumentException('You must pass an array parameter to the clean() method');
    }

    $this->doDirectPaymentTransaction($values);

    if (!$this->isValidTransaction())
    {
      throw new sfValidatorError($this, 'invalid', array('myval'=>'something'));      
    }

    return $values;
  }
  
  protected function doDirectPaymentTransaction($values)
  {
    if (strtolower(sfConfig::get('app_payflowpro_env')) == 'simulation')
    {
      $result = $this->simulateTransation(true);
    }
    else
    {
      $result = $this->performTransaction($values);
    }
    
    $this->responseArray = $this->convertResultToArray($result);
    
    return $this->evaluateTransactionResponse($this->responseArray);
    
  }
  
  protected function performTransaction($values)
  {
    $payPalQuery = $this->prepareQuery($values);
    
    $uniqueId = $this->payFlowProForm->getOrderNumber();
    
    $result = $this->postToPayPal($uniqueId, $payPalQuery);

    return $result;
  }
  
  protected function prepareQuery($values)
  {
    // Payment details
    $acct = $values['acct'];        //str_replace(' ','',$_POST['card_num']);
    $card = $values['card'];
    $cvv2 = $values['cvv2'];        // 123
    
    $expiry = date('my', strtotime($values['exp']));

    $amount = number_format($this->payFlowProForm->getAmount(),2);  // format to valid amount, ie removal of commas and 2-decimals.
    
    $orderNumber = $this->payFlowProForm->getOrderNumber();
        
    // Billing Details
    $fname = $values['fname'];
    $lname = $values['lname'];
    $email = $values['email'];
    $addr1 = $values['street'];
    $addr2 = $values['city'];
    $addr3 = $values['state'];
    $addr4 = $values['zip'];
    $country = $values['country'];        // 3-digits ISO code
    
    // Other information
    $custom = sfConfig::get('sf_environment') == 'prod' ? '' : 'Testing';
    $custIp = $_SERVER['REMOTE_ADDR'];
        
    $paypalQueryArray = array(
      'USER'       => sfConfig::get('app_payflowpro_user'),
      'VENDOR'     => sfConfig::get('app_payflowpro_vendor'),
      'PARTNER'    => sfConfig::get('app_payflowpro_partner'),
      'PWD'        => sfConfig::get('app_payflowpro_password'),
      'TENDER'     => 'C',  // C - Direct Payment using credit card
      'ACCT'       => $acct,
      'CVV2'       => $cvv2,
      'EXPDATE'    => $expiry,
      'ACCTTYPE'   => $card,
      'AMT'        => $amount,
      'CURRENCY'   => sfConfig::get('app_payflow_currency'),
      'FIRSTNAME'  => $fname,
      'LASTNAME'   => $lname,
      'STREET'     => $addr1,
      'CITY'       => $addr2,
      'STATE'      => $addr3,
      'ZIP'        => $addr4,
      'COUNTRY'    => $country,
      'EMAIL'      => $email,
      'CUSTIP'     => $custIp,
      'COMMENT1'   => $custom,
      'INVNUM'     => $orderNumber,
      'ORDERDESC'  => $this->payFlowProForm->getOrderDescription(),
      'VERBOSITY'  => sfConfig::get('app_payflow_verbosity', 'MEDIUM'),
      'CARDSTART'  => '',
      'CARDISSUE'  => '',
    );
    
    // Transcation Type
    $payPeriod = $this->payFlowProForm->getPaymentPeriod();
    if ($payPeriod == false) {
      $paypalQueryArray['TRXTYPE'] = 'S'; // a - authorization, s - sale
    } else {
      $paypalQueryArray = array_merge($paypalQueryArray, $this->getRecurringParameters($payPeriod, $this->payFlowProForm->getProfileName()));
    }

    foreach ($paypalQueryArray as $key => $value) 
    {
			$paypalQuery[] = $key.'['.strlen($value).']='.$value;
		}
		
		$paypalQuery = implode('&', $paypalQuery);
    
    return $paypalQuery;
  }
  
  protected function getRecurringParameters($payPeriod, $profileName)
  {
    return array(
      'TRXTYPE' => 'R',
      'ACTION' => 'A', // add
      'PROFILENAME' => $profileName,
      'START' => date('mdY', strtotime('+1 day')),
      'TERM' => 0,
      'PAYPERIOD' => $this->translatePayPeriod($payPeriod),
    );
    
  }
  
  protected function translatePayPeriod($payPeriod)
  {    
    $periods = array(
      'weekly' => 'WEEK',
      'biweekly' => 'BIWK',
      'semimonthly' => 'SMMO',
      'fourweeks' => 'FRWK',
      'monthly' => 'MONT',
      'quarterly' => 'QTER',
      'semiyearly' => 'SMYR',
      'yearly' => 'YEAR'
    );
    
    return isset($periods[$payPeriod]) ? $periods[$payPeriod] : null;
  }
  
  protected function postToPayPal($uniqueId, $data)
  {
    if (sfConfig::get('sf_logging_enabled'))
    {
      sfContext::getInstance()->getLogger()->info('{PayFlowPro} Post Data: "'.$data.'"');
    }
    
    // get data ready for API
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    $headers[] = "Content-Type: text/namevalue"; //or text/xml if using XMLPay.
    $headers[] = "Content-Length : " . strlen ($data);  // Length of data to be passed 
    // Here I set the server timeout value to 45, but notice below in the cURL section, I set the timeout
    // for cURL to 90 seconds.  You want to make sure the server timeout is less than the connection.
    $headers[] = "X-VPS-Timeout: 45";
    $headers[] = "X-VPS-Request-ID:" . $uniqueId;

    // Optional Headers.  If used adjust as necessary.
    /*
    $headers[] = "X-VPS-VIT-OS-Name: Linux";                    // Name of your OS
    $headers[] = "X-VPS-VIT-OS-Version: RHEL 4";                // OS Version
    $headers[] = "X-VPS-VIT-Client-Type: PHP/cURL";             // What you are using
    $headers[] = "X-VPS-VIT-Client-Version: 0.01";              // For your info
    $headers[] = "X-VPS-VIT-Client-Architecture: x86";          // For your info
    $headers[] = "X-VPS-VIT-Integration-Product: PHPv4::cURL";  // For your info, would populate with application name
    $headers[] = "X-VPS-VIT-Integration-Version: 0.01";         // Application version
    */
    
    $submitUrl = $this->getSubmitUrl();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $submitUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
    curl_setopt($ch, CURLOPT_HEADER, 1);                // tells curl to include headers in response
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);        // return into a variable
    curl_setopt($ch, CURLOPT_TIMEOUT, 90);              // times out after 90 secs
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);        // this line makes it work under https
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);        //adding POST data
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,  2);       //verifies ssl certificate
    curl_setopt($ch, CURLOPT_FORBID_REUSE, TRUE);       //forces closure of connection when done
    curl_setopt($ch, CURLOPT_POST, 1); 									//data sent as POST

    // echo $data; echo '<br><br>';
    
    /*
      Try to submit the transaction up to 3 times with 5 second delay.  This can be used
      in case of network issues.  The idea here is since you are posting via HTTPS there
      could be general network issues, so try a few times before you tell customer there
      is a problem.
    */

    $i=1;
    while ($i++ <= 3) {
      $result = curl_exec($ch);
      $headers = curl_getinfo($ch);
      //print_r($headers); echo '<br>'; print_r($result); echo '<br>';
      if ($headers['http_code'] != 200) {
        sleep(5);  // Let's wait 5 seconds to see if its a temporary network issue.
      }
      else if ($headers['http_code'] == 200) {
        // we got a good response, drop out of loop.
        break;
      }
    }
    // In this example I am looking for a 200 response from the server prior to continuing with
    // processing the order.  You can use this or other methods to validate a response from the
    // server and/or timeout issues due to network.
    curl_close($ch);
    
    if ($headers['http_code'] != 200) {  
      $this->throwError('no_response');
      return;
    }
    
    $result = strstr($result, "RESULT");
    
    if (sfConfig::get('sf_logging_enabled'))
    {
      sfContext::getInstance()->getLogger()->info('{PayFlowPro} Response : "'.$result.'"');
    }
    
    
    return $result;
  }

  protected function simulateTransation($valid = true)
  {
    if ($valid)
    {
      $result = "RESULT=0&PNREF=V18A1D85FB14&RESPMSG=Approved&AUTHCODE=586PNI&AVSADDR=Y&AVSZIP=Y&CVV2MATCH=Y&HOSTCODE=A&PROCAVS=Y&PROCCVV2=M&IAVS=N";  
    } else {
      $result = "RESULT=4&PNREF=V53A0A30B542&RESPMSG=Invalid amount";
    }
    
    return $result;
  }
  
  protected function evaluateTransactionResponse(array $response)
  {
    $this->isValidTransaction(false);
      
    $resultCode = $response['RESULT'];

    if ($resultCode == 1 || $resultCode == 26) 
    {
      /*
        This is just checking for invalid login credentials.  You normally would not display a custom message
        for this error.
        
        Result code 26 will be issued if you do not provide both the <vendor> and <user> fields.
        Remember: <vendor> = your merchant (login id), <user> = <vendor> unless you created a seperate <user> for Payflow Pro.
      
        Result code 1, user authentication failed, usually due to invalid account information or ip restriction on the account.
        You can verify ip restriction by logging into Manager.  See Service Settings >> Allowed IP Addresses.  
        Lastly it could be you forgot the path "/transaction" on the URL.
      */
      $this->throwError('account_config');
    } 
    else if ($resultCode == 0)
    {
      $this->isValidTransaction(true);
     
      /*
        Even though the transaction was approved, you still might want to check for AVS or CVV2(CSC) prior to
        accepting the order.  Do realize that credit cards are approved (charged) regardless of the AVS/CVV2 results.
        Should you decline (void) the transaction, the card will still have a temporary charge (approval) on it.

        Check AVS - Street/Zip
        In the default errors messages it shows what failed, ie street, zip or cvv2.  To prevent fraud, it is suggested
        you only give a generic billing error message and not tell the card-holder what is actually wrong.

        Also, it is totally up to you on if you accept only "Y" or allow "N" or "X".  You need to decide what
        business logic and liability you want to accept with cards that either don't pass the check or where
        the bank does not participate or return a result.  Remember, AVS is mostly used in the US but some foreign
        banks do participate.
      
        Remember, this just an example of what you might want to do.
        There should be some type of 3 strikes your out check
        Here you might want to put in code to flag or void the transaction depending on your needs.      
      */
      if (isset($response['AVSADDR']) && $response['AVSADDR'] != "Y") 
      {
        $this->throwError('avsaddr');
      }
      if (isset($response['AVSZIP']) && $response['AVSZIP'] != "Y") 
      {
        $this->throwError('avszip');
      }
      if (isset($response['CVV2MATCH']) && $response['CVV2MATCH'] != "Y") 
      {
        $this->throwError('cvv2match');
      }
    }
    else if ($resultCode == 12) 
    {
      // Hard decline from bank.
      $this->throwError('declined');
    }
    else if ($resultCode == 13) 
    {
      // Voice authorization required.
      $this->throwError('voice_authorization');
    }
    else if ($resultCode == 23 || $resultCode == 24) 
    {
      // Issue with credit card number or expiration date.
      $this->throwError('invalid_cc');
    }
    
    // Using the Fraud Protection Service.
    // This portion of code would be is you are using the Fraud Protection Service, this is for US merchants only.
    if (sfConfig::get('app_payflow_fraud_protection')) 
    {
      // 125, 126 and 127 are Fraud Responses.
      // Refer to the Payflow Pro Fraud Protection Services User's Guide or
      // Website Payments Pro Payflow Edition - Fraud Protection Services User's Guide.
      if ($resultCode == 125) 
      {
        // 125 = Fraud Filters set to Decline.
        $this->throwError('fraud_125');
      }
      else if ($resultCode == 126) 
      {
        /*
          126 = One of more filters were triggered.  Here you would check the fraud message returned if you
          want to validate data.  For example, you might have 3 filters set, but you'll allow 2 out of the
          3 to consider this a valid transaction.  You would then send the request to the server to modify the
          status of the transaction.  This outside the scope of this sample.  Refer to the Fraud Developer's Guide.
        */
        $this->throwError('fraud_126');
      }
      else if ($resultCode == 127) 
      {
        // 127 = Issue with fraud service.  Manually approve?
        $this->throwError('fraud_127');
      }
    }
    
    if (!$this->isValidTransaction())
    {
      $this->throwError('invalid');
    }
  }
  
  protected function convertResultToArray($result)
  {
    $proArray = array();

    while(strlen($result)){
      // name
      $keypos = strpos($result, '=');
      $keyval = substr($result, 0, $keypos);
      // value
      $valuepos = strpos($result, '&') ? strpos($result, '&'): strlen($result);
      $valval = substr($result, $keypos + 1, $valuepos - $keypos - 1);
      // decoding the respose
      $proArray[$keyval] = $valval;
      $result = substr($result, $valuepos + 1, strlen($result));
    }
    
    return $proArray;
  }
  
  protected function throwError($code, $placeholders = array())
  {
    $placeholders = array_merge($placeholders, $this->responseArray);
    throw new sfValidatorError($this, $code, $placeholders);
  }
  
}
