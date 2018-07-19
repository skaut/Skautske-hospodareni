<?php

declare(strict_types=1);

namespace App;

use Nette\Forms\Controls\MultiChoiceControl;
use Nette\StaticClass;
use Nextras\Forms\Controls\DatePicker;
use function array_intersect;
use function count;

class MyValidators
{
    use StaticClass;

    /**
     * @param mixed $control
     */
    public static function isValidDate($control) : bool
    {
        return $control->value === null ? false : true;
    }

    public static function isValidRange(DatePicker $end, \DateTimeInterface $start) : bool
    {
        return $start <= $end->getValue();
    }

    /**
     * @param mixed[] $values
     */
    public static function hasSelectedAny(MultiChoiceControl $control, array $values) : bool
    {
        return count(array_intersect($control->getValue(), $values)) !== 0;
    }
}
