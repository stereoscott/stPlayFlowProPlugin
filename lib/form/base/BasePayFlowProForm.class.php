<?php
/**
 * This Payflow class is based on the information found at:
 * http://paypaldeveloper.com/pdn/board/message?board.id=payflow&thread.id=1008
 *
 * @package stPayFlowProPlugin
 * @author Scott Meves
 */
class BasePayFlowProForm extends sfForm
{
  const ATTRIBUTE_NAMESPACE = 'stPayFlowPro/checkout';
  
  protected static $creditCardOptions = array('Visa' => 'Visa', 'Mastercard' => 'Mastercard', 'American Express' => 'American Express', 'Discover' => 'Discover');
  
  protected $amount, $orderNumber, $orderDescription;
  
  protected $transactionResponseArray, $responseMessage, $validTransaction;
  
  // Turn off CSRF protection, as on a split form the generated token on each page was differetnnt
  public function __construct($defaults = array(), $options = array(), $CSRFSecret = null)
  {
    parent::__construct($defaults, $options, false);
  }
  
  public function configure()
  {
    $years = range(date('Y'), date('Y') + 10);
    
    $this->setWidgets(array(
      'fname'   => new sfWidgetFormInput(array('label'=>'First Name')),
      'lname'   => new sfWidgetFormInput(array('label'=>'Last Name')),
      'street'  => new sfWidgetFormInput(),
      'city'    => new sfWidgetFormInput(),
      'state'   => new sfWidgetFormInput(),
      'zip'     => new sfWidgetFormInput(),
      'country' => new sfWidgetFormInput(),
      'email'   => new sfWidgetFormInput(),
      'acct'    => new sfWidgetFormInput(array('label'=>'Credit Card Number')),
      'cvv2'    => new sfWidgetFormInput(array('label'=>'CVV')),
      'card'    => new sfWidgetFormSelect(array('choices' => self::$creditCardOptions)),
      'exp'     => new sfWidgetFormDate(array('format'=>'%month%/%year%', 'years' => array_combine($years, $years))),
    ));
    
    $this->setValidators(array(
      'fname'   => new sfValidatorString(),
      'lname'   => new sfValidatorString(),
      'street'  => new sfValidatorString(),
      'city'    => new sfValidatorString(),
      'state'   => new sfValidatorString(),
      'zip'     => new sfValidatorString(),
      'country' => new sfValidatorString(),
      'email'   => new sfValidatorEmail(array('required' => false)),
      'acct'    => new sfValidatorString(),
      'cvv2'    => new sfValidatorString(),
      'card'    => new sfValidatorString(),
      'exp'     => new stValidatorExpirationDate(),
    ));
    
    $this->widgetSchema['state'] = new sfWidgetFormSelectUSState();
    $this->widgetSchema['country'] = new sfWidgetFormSelect(array('choices'=>array('US' => 'United States')));
    
    $this->widgetSchema->setHelp('fname', 'Please use the name and address that matches your credit card account.');
    $this->widgetSchema->setHelp('email', 'This email address will receive billing transaction details.');
    $this->widgetSchema->setHelp('cvv2', '3- or 4-digit code on printed on back of your card');
    
    $this->widgetSchema->setNameFormat('payment[%s]');
  
  } 
  
  public function setAmount($amount)
  {
    $this->amount = $amount;
  }
  
  public function getAmount()
  {
    return $this->amount;
  }
  
  public function getOrderNumber() 
  {
    return $this->orderNumber;
  }

  public function setOrderNumber($orderNumber) 
  {
    $this->orderNumber = $orderNumber;
  }
  
  public function getOrderDescription() 
  {
    return $this->orderDescription;
  }

  public function setOrderDescription($orderDescription) 
  {
    $this->orderDescription = $orderDescription;
  }
  
  public function isValidTransaction($value = null)
  {
    if (!is_null($value))
    {
      $this->validTransaction = (bool) $value;
    }
    
    return $this->validTransaction;
  }
  
  public function displayResponse()
  {
    if (!$this->transactionResponseArray) {
      throw new sfException('You must call '.__FUNCTION__.' after doDirectPaymentTransaction()');
    }
    
    $nvpArray = $this->transactionResponseArray;
    $responseMsg = $this->responseMessage;
    
    echo '<p>Results returned from server: <br><br>';
    while (list($key, $val) = each($nvpArray))
    {
      echo "\n" . $key . ": " . $val . "\n<br>";
    }
    echo '</p>';
    /* 
      Was this a duplicate transaction, ie the request ID was NOT changed.
      Remember, a duplicate response will return the results of the orignal transaction which
      could be misleading if you are debugging your software.
      For Example, let's say you got a result code 4, Invalid Amount from the orignal request because
      you were sending an amount like: 1,050.98.  Since the comma is invalid, you'd receive result code 4.
      RESULT=4&PNREF=V18A0C24920E&RESPMSG=Invalid amount&PREFPSMSG=No Rules Triggered
      Now, let's say you modified your code to fix this issue and ran another transaction but did not change
      the request ID.  Notice the PNREF below is the same as above, but DUPLICATE=1 is now appended.
      RESULT=4&PNREF=V18A0C24920E&RESPMSG=Invalid amount&DUPLICATE=1
      This would tell you that you are receving the results from a previous transaction.  This goes for
      all transactions even a Sale transaction.  In this example, let's say a customer ordered something and got
      a valid response and now a different customer with different credit card information orders something, but again
      the request ID is NOT changed, notice the results of these two sales.  In this case, you would have not received
      funds for the second order.
      First order: RESULT=0&PNREF=V79A0BC5E9CC&RESPMSG=Approved&AUTHCODE=166PNI&AVSADDR=X&AVSZIP=X&CVV2MATCH=Y&IAVS=X
      Second order: RESULT=0&PNREF=V79A0BC5E9CC&RESPMSG=Approved&AUTHCODE=166PNI&AVSADDR=X&AVSZIP=X&CVV2MATCH=Y&IAVS=X&DUPLICATE=1
      Again, notice the PNREF is from the first transaction, this goes for all the other fields as well.
      It is suggested that your use this to your benefit to prevent duplicate transaction from the same customer, but you want
      to check for DUPLICATE=1 to ensure it is not the same results as a previous one.
      */
    if (isset ($nvpArray['DUPLICATE']))
    {
      echo '<h2>Error!</h2><p>This is a duplicate of your previous order.</p>';
      echo '<p>Notice that DUPLICATE=1 is returned and the PNREF is the same ';
      echo 'as the previous one.  You can see this in Manager as the Transaction ';
      echo 'Type will be "N".';
    }
    if (isset($nvpArray['PPREF'])) 
    {
      // Check if PayPal Express Checkout and if order is Pending.
      if (isset($nvpArray['PENDINGREASON'])) 
      {
        if ($nvpArray['PENDINGREASON']=='completed') 
        {
          echo '<h2>Transaction Completed!</h2>';
          echo '<h3>'.$responseMsg.'</h3><p>';
          echo '<h4>Note: To simulate a duplicate transaction, refresh this page in your browser.  ';
          echo 'Notice that you will see DUPLICATE=1 returned.</h4>';
        } 
        elseif ($nvpArray['PENDINGREASON']=='echeck')
        {
          // PayPal transaction
          echo '<h2>Transaction Completed!</h2>';
          echo '<h3>The payment is pending because it was made by an eCheck that has not yet cleared.</h3';
        } 
        else 
        {
          // PENDINGREASON not 'completed' or 'echeck'.  See Integration guide for more responses.
          echo '<h2>Transaction Completed!</h2>';
          echo '<h3>The payment is pending due to: '.$nvpArray['PENDINGREASON'];
          echo '<h4>Please login to your PayPal account for more details.</h4>';
        }
      }
    } 
    else
    {
      if ($nvpArray['RESULT'] == "0") 
      {
        echo '<h2>Transaction Completed!</h2>';
      } 
      else 
      {
        echo '<h2>Transaction Failure!</h2>';
      }
      echo '<h3>'.$responseMsg.'</h3><p>';
      if ($nvpArray['RESULT'] != "26" && $nvpArray['RESULT'] != "1") 
      {
        echo '<h4>Note: To simulate a duplicate transaction, refresh this page in your browser.&nbsp';
        echo 'Notice that you will see DUPLICATE=1 returned.</h4>';
      }
    }
  }
  
  public function getProtectedValues()
  {
    $protectedValues = array();
    
    foreach ($this->values as $key => $value)
    {
      switch ($key)
      {
        case 'acct':
          $v = str_pad(substr($value, -4), strlen($value), '*', STR_PAD_LEFT);
          break;    
        case 'cvv2':
          $v = '****';
          break;
        default:
          $v = $value;
          break;
      }
      
      $protectedValues[$key] = $v;      
    }
    
    return $protectedValues;
  }
}


?>