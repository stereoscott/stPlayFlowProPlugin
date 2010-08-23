<?php /* if ($showResponse): echo $form->displayResponse(); endif */ ?>
<h2>Step 1: Billing Address</h2>

<form action="<?php url_for('payflow/step1') ?>" method="post">
  <ul>
  <?php echo $form->renderUsing('List') ?>
  </ul>
  <input type="submit" name="_next" value="Continue to Step 2" />
</form>