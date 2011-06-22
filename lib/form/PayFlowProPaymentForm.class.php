<?php
/**
 * This Payflow class is based on the information found at:
 * http://paypaldeveloper.com/pdn/board/message?board.id=payflow&thread.id=1008
 *
 * @package stPayFlowProPlugin
 * @author Scott Meves
 */
class PayFlowProPaymentForm extends BasePayFlowProForm
{
  public function configure()
  {
    parent::configure();
    
    $fields = array(      
      'acct',
      'cvv2',
      'card',
      'exp',
    );
    
        
    $this->useFields($fields);
    
    foreach ($fields as $field) {
      $this->validatorSchema[$field]->setOption('required', true);
    }
    
  }
}


?>