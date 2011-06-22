<?php
// see http://gist.github.com/384001
class stValidatorExpirationDate extends sfValidatorDate
{
  protected function convertDateArrayToString($value)
  {

    // all elements must be empty or a number
    foreach (array('year', 'month') as $key)
    {
      if (isset($value[$key]) && !preg_match('#^\d+$#', $value[$key]) && !empty($value[$key]))
      {
        throw new sfValidatorError($this, 'invalid', array('value' => $value));
      }
    }

    // if one date value is empty, all others must be empty too
    $empties =
      (!isset($value['year']) || !$value['year'] ? 1 : 0) +
      (!isset($value['month']) || !$value['month'] ? 1 : 0)
    ;
    if ($empties > 0 && $empties < 2)
    {
      throw new sfValidatorError($this, 'invalid', array('value' => $value));
    }
    else if (2 == $empties)
    {
      return $this->getEmptyValue();
    }
    
    if (!checkdate(intval($value['month']), '01', intval($value['year'])))
    {
      throw new sfValidatorError($this, 'invalid', array('value' => $value));
    }

    if ($this->getOption('with_time'))
    {
      // if second is set, minute and hour must be set
      // if minute is set, hour must be set
      if (
        $this->isValueSet($value, 'second') && (!$this->isValueSet($value, 'minute') || !$this->isValueSet($value, 'hour'))
        ||
        $this->isValueSet($value, 'minute') && !$this->isValueSet($value, 'hour')
      )
      {
        throw new sfValidatorError($this, 'invalid', array('value' => $value));
      }

      $clean = mktime(
        0, 0, 0,
        intval($value['month']),
        1,
        intval($value['year'])
      );
    }
    else
    {
      $clean = mktime(0, 0, 0, intval($value['month']), 1, intval($value['year']));
    }

    if (false === $clean)
    {
      throw new sfValidatorError($this, 'invalid', array('value' => var_export($value, true)));
    }

    return $clean;

  }
  
}
