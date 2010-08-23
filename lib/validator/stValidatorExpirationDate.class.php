<?php

class stValidatorExpirationDate extends sfValidatorDate
{
  protected function convertDateArrayToTimestamp($value)
  {

    // all elements must be empty or a number
    foreach (array('year', 'month') as $key)
    {
      if (isset($value[$key]) && !preg_match('#^\d+$#', $value[$key]) && !empty($value[$key]))
      {
        throw new sfValidatorError($this, 'invalid', array('value' => $value));
      }
    }

    $clean = mktime(0, 0, 0, intval($value['month']), 1, intval($value['year']));

    if (false === $clean)
    {
      throw new sfValidatorError($this, 'invalid', array('value' => var_export($value, true)));
    }
    
    return $clean;
  }
  
}
