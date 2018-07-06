<?php

use Nette\Forms\Controls\MultiChoiceControl;
use Nextras\Forms\Controls\DatePicker;


class MyValidators
{
    use \Nette\StaticClass;

    public static function isValidDate($control) : bool
    {
        return $control->value === NULL ? FALSE : TRUE;
    }

    public static function isValidRange(DatePicker $end, DateTimeInterface $start) : bool
    {
        return $start <= $end->getValue();
    }

    public static function hasSelectedAny(MultiChoiceControl $control, array $values) : bool
    {
        return count(array_intersect($control->getValue(), $values)) !== 0;
    }

}
