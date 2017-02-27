<?php

use Nextras\Forms\Controls\DatePicker;

/**
 *
 * @author Sin
 */
class MyValidators
{
    public static function isValidDate($control /*, $arg]*/) : bool
    {
        return $control->value === NULL ? FALSE : TRUE;
    }

    public static function isValidRange(DatePicker $end, DateTimeInterface $start) : bool
    {
        return $start <= $end->getValue();
    }
}
