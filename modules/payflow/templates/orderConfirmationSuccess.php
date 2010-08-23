<h2>Your order has been processed!</h2>
<ul>
<?php foreach($payFlowForm as $field): ?>  
  <?php if(!$field->isHidden()): ?>  
    <li><?php echo $field->renderLabel() ?>: <?php echo $field->getValue() ?></li>
  <?php endif; ?>  
<?php endforeach; ?>
</ul>