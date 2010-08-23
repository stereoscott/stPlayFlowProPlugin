<?php
/**
 * This Payflow class is based on the information found at:
 * http://paypaldeveloper.com/pdn/board/message?board.id=payflow&thread.id=1008
 *
 * @package stPayFlowProPlugin
 * @author Scott Meves
 */
class PayFlowProAddressForm extends BasePayFlowProForm
{
  public function configure()
  {
    parent::configure();
    
    $this->useFields(array(
      'fname',
      'lname',
      'street',
      'city',
      'state',
      'zip',
      'country',
      'email',
    ));

    $this->widgetSchema['state'] = new sfWidgetFormSelectUSState();
    $this->widgetSchema['country'] = new sfWidgetFormSelect(array('choices'=>array('US' => 'United States')));
    $promoForm = new PromoCodeCheckoutForm();
    $this->embedForm('PromoCodeCheckout', $promoForm);
    
    
    $this->validatorSchema['email'] = new sfValidatorEmail();
    
    $requiredFields = array('lname', 'street', 'city', 'state', 'zip', 'country', 'email');
    foreach ($requiredFields as $field) {
      $this->validatorSchema[$field]->setOption('required', true);
    }
    
    
    $this->widgetSchema->setLabel('PromoCodeCheckout', ' ');
    $this->widgetSchema->setHelp('fname', 'Please use the name and address that matches your credit card account.');
    $this->widgetSchema->setHelp('email', 'This email address will receive billing transaction details.');
    $this->widgetSchema->setFormFormatterName('simple');
    
  }
    
  public function getPromoCode()
  {
    if ($this->isBound) {
      $promoCodeCheckout = $this->getValue('PromoCodeCheckout');
      if ($promoCodeCheckout && isset($promoCodeCheckout['code'])) {
        $code = $promoCodeCheckout['code'];
        return Doctrine::getTable('PromoCode')->findValidByCode($code);
      }
    }
    
    return null;
  }
  
}


?>