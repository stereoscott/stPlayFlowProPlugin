<?php
class stPayFlowProEXAMPLE
{
  /**
   * The absolute URL to the Authorize.net gateway
   *
   * @var string
   */
  public $authorize_url = "https://secure.authorize.net/gateway/transact.dll";
      
  /**
   * Username to connect to the Authorize.net gateway
   *
   * @var string
   */
  private $authorize_username;
  
  /**
   * Password to connect to the Authorize.net gateway
   *
   * @var string
   */
  private $authorize_password;
  
  /**
   * Authorize.net gateway API version
   *
   * @var string
   */
  private $authorize_version = "3.1";
  
  /**
   * The type of authorization method you are attempting to process
   * Valid types:
   *   CC, ECHECK (not yet supported)
   *   
   * @var string
   */
  private $authorize_method = "CC";
  
  /**
   * The type of transaction you are submitting to Authorize.net
   * Valid types:
   *   AUTH_CAPTURE, AUTH_ONLY, CAPTURE_ONLY, CREDIT, VOID, PRIOR_AUTH_CAPTURE
   *
   * @var string
   */
  private $authorize_type = "AUTH_CAPTURE";
  
  /**
   * A custom hash for verifying the authenticity of a transaction.
   * This is an optional field, yet is recommended for security purposes.
   *
   * @var string
   */
  private $authorize_hash = "";
  
  /**
   * When test mode is enabled, transactions will not be processed,
   * although resulting response messages will simulate a live transaction.
   *
   * @var bool
   */
  private $authorize_test_mode = false;
  
  /**
   * Array variable that holds all of the transaction data
   *
   * @var array
   */
  public $data = array();
  
  /**
   * URI that will be sent to the Authorize.net gateway URL
   *
   * @var string
   */
  private $query_string;
  
  /**
   * Authorize.net gateway construtor
   *
   * <b>Options:</b>
   * - hash    - Security hash to be passed with your transaction. Optional, yet reccommended for security purposes.
   * - method  - Method by which the customer is paying.
   * - type    - The type of transaction you are submitting to Authorize.net
   * - version - Authorize.net gateway API version
   * - url     - The absolute URL to the Authorize.net gateway
   * - test    - Boolean value, enable or disable test mode
   * @param string $username - Authorize.net gateway username
   * @param string $password (optional) - Authorize.net gateway password
   * @param array  $options (optional) 
   */
  public function __construct($username, $password = null, $options = array())
  {
    $this->setAuthorizeUsername($username);
    if (isset($password)) $this->setAuthorizePassword($password);
    
    if (isset($options['hash'])) $this->setAuthorizeHash($options['hash']);
    if (isset($options['method'])) $this->setAuthorizeMethod($options['method']);
    if (isset($options['type'])) $this->setAuthorizeType($options['type']);
    if (isset($options['version'])) $this->setAuthorizeVersion($options['version']);
    if (isset($options['url'])) $this->setAuthorizeUrl($options['url']);
    if (isset($options['test'])) $this->setAuthorizeTestMode($options['test']);
  }
    
  # AUTHORIZE.NET METHODS  
  private function setAuthorizeUrl($url)
  {
    $this->authorize_url = $url;
  }
  
  /**
   * get Authorize.net gateway URL
   *
   * @return string
   */
  private function getAuthorizeUrl()
  {
    return $this->authorize_url;
  }
  
  private function setAuthorizeUsername($username)
  {
    $this->authorize_username = $username;
  }
  
  /**
   * get Authorize.net gateway username
   *
   * @return string
   */
  private function getAuthorizeUsername()
  {
    return $this->authorize_username;
  }
  
  private function setAuthorizePassword($password)
  {
    $this->authorize_password = $password;
  }
  
  /**
   * get Authorize.net gateway password
   *
   * @return string
   */
  private function getAuthorizePassword()
  {
    return $this->authorize_password;
  }
  
  private function setAuthorizeHash($hash)
  {
    $this->authorize_hash = $hash;
  }
  
  /**
   * get Authorize.net security hash
   *
   * @return string
   */
  private function getAuthorizeHash()
  {
    return $this->authorize_hash;
  }
  
  private function setAuthorizeType($type)
  {
    $this->authorize_type = $type;
  }
  
  /**
   * get Authorize.net transaction type
   *
   * @return string
   */
  private function getAuthorizeType()
  {
    return $this->authorize_type;
  }
  
  private function setAuthorizeVersion($type)
  {
    $this->authorize_version = $type;
  }
  
  /**
   * get Authorize.net gateway version
   *
   * @return string (decimal)
   */
  private function getAuthorizeVersion()
  {
    return $this->authorize_version;
  }
  
  private function setAuthorizeMethod($method)
  {
    $this->authorize_method = $method;
  }
  
  /**
   * get Authorize.net payment method
   *
   * @return string
   */
  private function getAuthorizeMethod()
  {
    return $this->authorize_method;
  }
  
  private function setAuthorizeTestMode($bool = true)
  {
    $this->authorize_test_mode = $bool;
  }
  
  /**
   * get Authorize.net test mode status
   *
   * @return bool
   */
  private function getAuthorizeTestMode()
  {
    return $this->authorize_test_mode;
  }
  
  public function setInvoiceNumber($invoice_number)
  {
    $this->data['transaction']['x_invoice_num'] = $invoice_number;
  }
  
  /**
   * get internal invoice number
   *
   * @return string
   */  
  public function getInvoiceNumber()
  {
    return $this->data['transaction']['x_invoice_num'];
  }
  
  public function setCustomerNumber($customer_number)
  {
    $this->data['transaction']['x_cust_id'] = $customer_number;
  }
  
  /**
   * get internal customer number
   *
   * @return string
   */  
  public function getCustomerNumber()
  {
    return $this->data['transaction']['x_cust_id'];
  }
  
  public function setTransactionAmount($amount)
  {
    $this->data['transaction']['x_amount'] = $amount;
  }

  /**
   * get transaction amount (price)
   *
   * @return decimal
   */  
  public function getTransactionAmount()
  {
    return $this->data['transaction']['x_amount'];
  }
  
  public function setTransactionDescription($description)
  {
    $this->data['transaction']['x_description'] = $description;
  }
  
  /**
   * get description of transaction (description of products or services)
   *
   * @return string
   */  
  public function getTransactionDescription()
  {
    return $this->data['transaction']['x_description'];
  }
  
  /**
   * set authorization code
   *
   * @param string $auth_code
   */
  public function setAuthCode($auth_code)
  {
    $this->data['transaction']['x_auth_code'] = $auth_code;
  }
  
  /**
   * get authorization code
   *
   * @return string
   */
  public function getAuthCode()
  {
    return $this->data['transaction']['x_auth_code'];
  }
  
  /**
   * set transaction id
   *
   * @param string $transaction_id
   */  
  public function setTransactionID($transaction_id)
  {
    $this->data['transaction']['x_trans_id'] = $transaction_id;
  }
  
  /**
   * get transaction id
   *
   * @return string
   */   
  public function getTransactionID()
  {
    return $this->data['transaction']['x_trans_id'];
  }
  
  /**
   * get array containing all transaction information
   *
   * @return array
   */   
  public function getTransactionData()
  {
    return $this->data['transaction'];
  }
  
  /**
   * set credit card number
   *
   * @param string $number
   */
  public function setCardNumber($number)
  {
    $this->data['card']['x_card_num'] = $number;
  }
  
  /**
   * get credit card number
   *
   * @return string
   */ 
  public function getCardNumber()
  {
    return $this->data['card']['x_card_num'];
  }
  
  /**
   * set credit card expiration date (month and year)
   * 
   *
   * @param string $expiration
   */  
  public function setCardExpiration($expiration)
  {
    $this->data['card']['x_exp_date'] = $expiration;
  }
  
  /**
   * get credit card expiration date
   *
   * @return string
   */   
  public function getCardExpiration()
  {
    return $this->data['card']['x_exp_date'];
  }
  
  /**
   * set credit card security code
   *
   * @param integer $code
   */  
  public function setCardSecurityCode($code)
  {
    $this->data['card']['x_card_code'] = $code;
  }
  
  /**
   * get credit card CVV2 / security code
   *
   * @return integer
   */   
  public function getCardSecurityCode()
  {
    return $this->data['card']['x_card_code'];
  }
  
  /**
   * get array containing all credit card information
   *
   * @return string
   */   
  public function getCardData()
  {
    return $this->data['card'];
  }  
   
  /**
   * set billing first name
   *
   * @param string $first_name
   */
  public function setBillingFirstName($first_name)
  {
    $this->data['billing']['x_first_name'] = $first_name;
  }
  
  /**
   * get billing first name
   *
   * @return string
   */   
  public function getBillingFirstName()
  {
    return $this->data['billing']['x_first_name'];
  }
  
  /**
   * set billing last name
   *
   * @param string $last_name
   */
  public function setBillingLastName($last_name)
  {
    $this->data['billing']['x_last_name'] = $last_name;
  }
  
  /**
   * get billing last name
   *
   * @return string
   */
  public function getBillingLastName()
  {
    return $this->data['billing']['x_last_name'];
  }

  /**
   * set billing company name
   *
   * @param string $company
   */
  public function setBillingCompany($company)
  {
    $this->data['billing']['x_company'] = $company;
  }
  
  /**
   * get billing company name
   *
   * @return string
   */  
  public function getBillingCompany()
  {
    return $this->data['billing']['x_company'];
  }
  
  /**
   * set billing address
   *
   * @param string $address
   */
  public function setBillingAddress($address)
  {
    $this->data['billing']['x_address'] = $address;
  }
  
  /**
   * get billing street address
   *
   * @return string
   */  
  public function getBillingAddress()
  {
    return $this->data['billing']['x_address'];
  }
  
  /**
   * set billing city
   *
   * @param string $city
   */
  public function setBillingCity($city)
  {
    $this->data['billing']['x_city'] = $city;
  }
  
  /**
   * get billing ciy
   *
   * @return string
   */  
  public function getBillingCity()
  {
    return $this->data['billing']['x_city'];
  }
  
  /**
   * set billing state
   *
   * @param string $state
   */
  public function setBillingState($state)
  {
    $this->data['billing']['x_state'] = $state;
  }
  
  /**
   * get billing state
   *
   * @return string
   */  
  public function getBillingState()
  {
    return $this->data['billing']['x_state'];
  }  
  
  /**
   * set billing postal code / zip code
   *
   * @param string $postal_code
   */
  public function setBillingPostalCode($postal_code)
  {
    $this->data['billing']['x_zip'] = $postal_code;
  }
  
  /**
   * get billing postal code / zip code
   *
   * @return string
   */  
  public function getBillingPostalCode()
  {
    return $this->data['billing']['x_zip'];
  }
  
  /**
   * set billing country
   *
   * @param string $country
   */
  public function setBillingCountry($country)
  {
    $this->data['billing']['x_country'] = $country;
  }
  
  /**
   * get billing country
   *
   * @return string
   */  
  public function getBillingCountry()
  {
    return $this->data['billing']['x_country'];
  }
  
  /**
   * set billing phone number
   *
   * @param string $phone
   */
  public function setBillingPhone($phone)
  {
    $this->data['billing']['x_phone'] = $phone;
  }
  
  /**
   * get billing phone number
   *
   * @return string
   */  
  public function getBillingPhone()
  {
    return $this->data['billing']['x_phone'];
  }
  
  /**
   * set billing e-mail address
   *
   * @param string $email
   */
  public function setBillingEmail($email)
  {
    $this->data['billing']['x_email'] = $email;
  }
  
  /**
   * get billing e-mail address
   *
   * @return string
   */  
  public function getBillingEmail()
  {
    return $this->data['billing']['x_email'];
  }
  
  /**
   * get array containing all billing information
   *
   * @return array
   */  
  public function getBillingData()
  {
    return $this->data['billing'];
  }

  /**
   * set shipping first name
   *
   * @param string $first_name
   */
  public function setShippingFirstName($first_name)
  {
    $this->data['shipping']['x_ship_to_first_name'] = $first_name;
  }
  
  /**
   * get shipping first name
   *
   * @return string
   */  
  public function getShippingFirstName()
  {
    return $this->data['shipping']['x_ship_to_first_name'];
  }
  
  /**
   * set shipping last name
   *
   * @param string $last_name
   */
  public function setShippingLastName($last_name)
  {
    $this->data['shipping']['x_ship_to_last_name'] = $last_name;
  }
  
  /**
   * get shipping last name
   *
   * @return string
   */   
  public function getShippingLastName()
  {
    return $this->data['shipping']['x_ship_to_last_name'];
  }

  /**
   * set shipping company name
   *
   * @param string $company
   */
  public function setShippingCompany($company)
  {
    $this->data['shipping']['x_ship_to_company'] = $company;
  }
  
  /**
   * get shipping company name
   *
   * @return string
   */   
  public function getShippingCompany()
  {
    return $this->data['shipping']['x_ship_to_company'];
  }
  
  /**
   * set shipping street address
   *
   * @param string $address
   */
  public function setShippingAddress($address)
  {
    $this->data['shipping']['x_ship_to_address'] = $address;
  }
  
  /**
   * get shipping street address
   *
   * @return string
   */   
  public function getShippingAddress()
  {
    return $this->data['shipping']['x_ship_to_address'];
  }
  
  /**
   * set shipping city
   *
   * @param string $city
   */
  public function setShippingCity($city)
  {
    $this->data['shipping']['x_ship_to_city'] = $city;
  }
  
  /**
   * get shipping city
   *
   * @return string
   */   
  public function getShippingCity()
  {
    return $this->data['shipping']['x_ship_to_city'];
  }
  
  /**
   * set shipping state
   *
   * @param string $state
   */
  public function setShippingState($state)
  {
    $this->data['shipping']['x_ship_to_state'] = $state;
  }
  
  /**
   * get shipping state
   *
   * @return string
   */  
  public function getShippingState()
  {
    return $this->data['shipping']['x_ship_to_state'];
  }
  
  /**
   * set shipping postal code / zip code
   *
   * @param string $postal_code
   */  
  public function setShippingPostalCode($postal_code)
  {
    $this->data['shipping']['x_ship_to_zip'] = $postal_code;
  }
  
  /**
   * get shipping postal code / zip code
   *
   * @return string
   */   
  public function getShippingPostalCode()
  {
    return $this->data['shipping']['x_ship_to_zip'];
  }
  
  /**
   * set shipping country
   *
   * @param string $country
   */
  public function setShippingCountry($country)
  {
    $this->data['shipping']['x_ship_to_country'] = $country;
  }
  
  /**
   * get shipping country
   *
   * @return string
   */   
  public function getShippingCountry()
  {
    return $this->data['shipping']['x_ship_to_country'];
  }
  
  /**
   * get array containing all shipping information
   *
   * @return array
   */   
  public function getShippingData()
  {
    return $this->data['shipping'];
  }
  
  /**
   * Shortcut method to copy billing information to shipping information
   *
   */
  public function copyBillingToShipping()
  {
    $this->setShippingFirstName($this->getBillingFirstName());
    $this->setShippingLastName($this->getBillingLastName());
    $this->setShippingAddress($this->getBillingAddress());
    $this->setShippingCity($this->getBillingCity());
    $this->setShippingState($this->getBillingState());
    $this->setShippingPostalCode($this->getBillingPostalCode());
    $this->setShippingCountry($this->getBillingCountry());
  }
  
  /**
   * get an array containing all transaction data
   *
   * @return array
   */
  public function getData()
  {
    return $this->data;
  }
  
  /**
   * Append a URI parameter to the query string
   *
   * @param string $parameter
   * @param string $value
   */
  private function addQueryParameter($parameter, $value)
  {
    if (isset($value) AND !empty($value)) $this->setQueryString($this->getQueryString() . $parameter . '=' . trim($value) . '&');
  }
  
  /**
   * get query URI
   *
   * @return string
   */  
  private function getQueryString()
  {
    return $this->query_string;
  }

  /**
   * set query URI
   *
   * @param string $string
   */
  private function setQueryString($string)
  {
    return $this->query_string = $string;
  }
  
  /**
   * Format the query string for transport
   *
   * @param string $string
   * @return string
   */
  private function cleanQueryString($string)
  {
    $string = str_replace(' ', '+', $string);
    
    return $string;
  }
    
  /**
   * Parse the query parameters into a URI string for the gateway
   *
   * @return string
   */
  private function prepareQueryString()
  {
    $this->addQueryParameter('x_delim_data', 'TRUE');
    $this->addQueryParameter('x_login', $this->getAuthorizeUsername());
    $this->addQueryParameter('x_password', $this->getAuthorizePassword());
    $this->addQueryParameter('x_version', $this->getAuthorizeVersion());
    $this->addQueryParameter('x_type', $this->getAuthorizeType());
    $this->addQueryParameter('x_method', $this->getAuthorizeMethod());
    if ($this->getAuthorizeTestMode()) $this->addQueryParameter('x_test_request', 'TRUE');
        
    // Build Query String
    if ($data = $this->getData())
    {
      foreach ($data as $group)
      {
        foreach ($group as $param => $value)
        {
          $this->addQueryParameter($param, $value);
        }
      }
    }
    
    return $this->cleanQueryString(substr($this->getQueryString(), 0, -1));
  }
  
  /**
   * Get the transaction resulting message based on the API result code
   *
   * @param string $code
   * @return string
   */
  public function getResultByCode($code)
  {
    if (!empty($code))
    {
      switch ($code)
      {
        case "A":
          $msg = "Address (Street) matches, ZIP does not";
          break;
          
        case "B":
          $msg = "Address information not provided for AVS check";
          break;
        
        case "E":
          $msg = "AVS error";
          break;
        
        case "G":
          $msg = "Non-U.S. Card Issuing Bank";
          break;
    
        case "N":
          $msg = "No Match on Address (Street) or ZIP";
          break;
    
        case "P":
          $msg = "AVS not applicable for this transaction";
          break;
          
        case "R":
          $msg = "Retry ñ System unavailable or timed out";
          break;
    
        case "S":
          $msg = "Service not supported by issuer";
          break;
          
        case "U":
          $msg = "Address information is unavailable";
          break;
    
        case "W":
          $msg = "9 digit ZIP matches, Address (Street) does not";
          break;
    
        case "X":
          $msg = "Address (Street) and 9 digit ZIP match";
          break;
    
        case "Y":
          $msg = "Address (Street) and 5 digit ZIP match";
          break;
          
        case "Z":
          $msg = "5 digit ZIP matches, Address (Street) does not";
          break;
        
        case "NO_RESPONSE":
          $msg = "Could not contact authorization gateway. Please try again later.";
          break;
          
        default:        
          $msg = "Unknown result code: \"" . $code . "\"";
          break;
      }
    }
    else 
    {
      $msg = "Result code was empty";
    }
    
    return $msg;
  }
  
  /**
   * Check the md5 security hash against the one returned by Authorize.net. 
   * If it does not match, we assume that the transaction has been tampered with and decline the transaction
   *
   * @param array $transaction_result
   * @return bool
   */
   // NO LONGER USED!!!
  public function isValidTransaction($transaction_response)
  {
    $check = $this->getAuthorizeHash() . $this->getAuthorizeUsername() . $transaction_response[6] . $this->getTransactionAmount();
    $md5_check = strtoupper(md5($check));

    if ($transaction_response[37] == $md5_check)
    {
      return true;
    }
		
    return false;
  }
  
  /**
   * Execute the Authorize.net gateway transaction
   *
   * @return array - Returns several array parts with transaction information
   */
  public function execute()
  {
    // Prepare the query
    $query_string = $this->prepareQueryString();
  
    if (in_array("curl", get_loaded_extensions()))
    {
      $ch = curl_init($this->getAuthorizeUrl());
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
      curl_setopt($ch, CURLOPT_REFERER, "");
      $response = curl_exec($ch);
      curl_close($ch);
      
      $result = array();
      $result['input_data'] = $this->getData();
      $result['query_string'] = $query_string;        
      
      // TO DO, PARSE RESULT AND PROCESS AVS RETURN CODES
      if (!$response)
      {
        $result['message'] = $this->getResultByCode("NO_RESPONSE");
      }
      else 
      {       

        $response = explode(",", $response);
      
        $result['response'] = $response;
        
          
        // If the transaction is a success
        if ($response[0] == 1) // commented out isValidTransaction
        {
          $result['success'] = 1;
          $result['message'] = $this->getResultByCode($response[5]);
        }
        
        // If the transaction failed
        else 
        {
          $result['success'] = 0;
          
          // If the transaction was declined based in user input
          if ($response[0] == 2)
          {
            $result['message'] = $this->getResultByCode($response[5]);
          }
          
          // If the transaction was declined for other reasons
          elseif ($response[0] == 3)
          {
            $result['message'] = $response[3];
          }
        }        
      }
      
      return $result;
    }
    else 
    {
      throw new sfException('Could not load cURL libraries. Make sure PHP is compiled with cURL');
    }
  }
}