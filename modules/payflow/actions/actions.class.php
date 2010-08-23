<?php

require_once dirname(__FILE__).'/../lib/BasepayflowActions.class.php';

/**
 * payflow actions.
 * 
 * @package    stPayflowProPlugin
 * @subpackage payflow
 * @author     Scott Meves <scott@stereointeractive.com>
 * @version    SVN: $Id: actions.class.php 12534 2008-11-01 13:38:27Z Kris.Wallsmith $
 */
class payflowActions extends BasepayflowActions
{
  
  public function executeStep1(sfWebRequest $request)
  {
    $this->form = new PayFlowProAddressForm();

    if ($request->isMethod('post'))
    {
      $this->form->bind($request->getParameter('payment'));
      
      if ($this->form->isValid())
      {
        $this->getUser()->setAttribute(BasePayFlowProForm::ATTRIBUTE_NAMESPACE, $request->getParameter('payment'));
        
        $this->redirect('payflow/step2');
      }
    } 
    else 
    {
      $this->form->bind($this->getUser()->getAttribute(BasePayFlowProForm::ATTRIBUTE_NAMESPACE));
    }
  }
  
  public function executeStep2(sfWebRequest $request)
  {
    // populate address form for confirmation
    $this->addressForm = new PayFlowProAddressForm();
    $this->addressForm->bind($this->getUser()->getAttribute(BasePayFlowProForm::ATTRIBUTE_NAMESPACE));
    
    // initialize payment form to handle credit card information
    $this->paymentForm = new PayFlowProPaymentForm(array(
      'acct' => 5105105105105100,
      'cvv2' => 123,
      'mm' => 12,
      'yy' => 2011,
    ));
    
    $this->paymentForm->setAmount('1.00');
    
    $this->showResponse = false;
    
    if ($request->isMethod('post'))
    {
      if ($request->hasParameter('_back'))
      {
        $this->redirect('payflow/step1');
      }
      elseif ($request->hasParameter('_next'))
      {
        $this->paymentForm->bind($request->getParameter('payment'));
        
        if ($this->paymentForm->isValid())
        {
          $values = array_merge($this->paymentForm->getValues(), $this->getUser()->getAttribute(BasePayFlowProForm::ATTRIBUTE_NAMESPACE));

          $this->payFlowForm = new PayFlowProForm();
          $this->payFlowForm->bind($values);

          $this->payFlowForm->doDirectPaymentTransaction();
          
          if ($this->payFlowForm->isValidTransaction()) {
            
            $protectedValues = $this->payFlowForm->getProtectedValues();
            $this->getUser()->setAttribute(BasePayFlowProForm::ATTRIBUTE_NAMESPACE.'/confirmation', $protectedValues);
            
            $this->redirect('payflow/orderConfirmation');
          }
          else
          {
            $this->showResponse = true;
          }
        }
      }
    }
  }

  public function executeOrderConfirmation(sfWebRequest $request)
  {
    $this->payFlowForm = new PayFlowProForm();
    $this->payFlowForm->bind($this->getUser()->getAttribute(BasePayFlowProForm::ATTRIBUTE_NAMESPACE.'/confirmation'));
  }
}
