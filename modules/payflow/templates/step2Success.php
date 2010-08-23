<?php /* if ($showResponse): echo $form->displayResponse(); endif */ ?>

<h2>Step 2: Payment Details</h2>
<strong>Please confirm your billing address below.</strong>
<ul>
<?php foreach($addressForm as $field): ?>  
  <?php if(!$field->isHidden()): ?>  
    <li><?php echo $field->renderLabel() ?>: <?php echo $field->getValue() ?></li>
  <?php endif; ?>  
<?php endforeach; ?>
</ul>

<h2>Payment Information</h2>

<?php if ($showResponse): echo $payFlowForm->displayResponse(); endif ?>

<form action="<?php url_for('payflow/step2') ?>" method="post">
  <ul>
  <?php echo $paymentForm->renderUsing('List') ?>
  </ul>
  <input type="submit" name="_next" value="Process Payment" />
  <input type="submit" name="_back" value="Go Back">
  
</form>