<?php
/**
 * This Payflow class is based on the information found at:
 * http://paypaldeveloper.com/pdn/board/message?board.id=payflow&thread.id=1008
 *
 * @package stPayFlowProPlugin
 * @author Scott Meves
 */
class PayFlowProForm extends BasePayFlowProForm
{
  protected $paymentPeriod = false;
  protected $profileName = false;
  
  public function configure()
  {
    parent::configure();

    $promoForm = new PromoCodeCheckoutForm();
    $this->embedForm('PromoCodeCheckout', $promoForm);    
    
    $this->widgetSchema->setLabel('PromoCodeCheckout', ' ');
    
    $this->validatorSchema->setPostValidator(new stValidatorPayFlowProTransaction($this));
  }
  
  public function getPostValidator()
  {
    return $this->validatorSchema->getPostValidator();
  }
  
  public function getPaymentPeriod() {
    return $this->paymentPeriod;
  }

  public function setPaymentPeriod($v) {
    $this->paymentPeriod = $v;
  }

  public function getProfileName() {
    return $this->profileName;
  }

  public function setProfileName($v) {
    $this->profileName = $v;
  }

  
  
  /*
  public function processTransaction(array $taintedValues = null, array $taintedFiles = null)
  {
    $this->bind($taintedValues, $taintedFiles);
    
    try {
      $this->doDirectPaymentTransaction();
    } 
    catch (sfValidatorErrorSchema $e)
    {
      $this->getErrorSchema()->addError('general', 'error');
    }
  }
  */
}

?>