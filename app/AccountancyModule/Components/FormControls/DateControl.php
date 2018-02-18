<?php

declare(strict_types=1);

namespace App\AccountancyModule\Components\FormControls;

use Cake\Chronos\Date;
use Nextras\Forms\Controls\DatePicker;

/**
 * Datetime picker with automatic string <-> Date conversion
 */
class DateControl extends DatePicker
{

    /**
     * @param Date|NULL $value
     * @return static
     */
    public function setValue($value)
    {
        if ( ! $value instanceof Date && $value !== NULL) {
            throw new \InvalidArgumentException(sprintf('$value must be instance of %s or NULL', Date::class));
        }

        return parent::setValue($value);
    }

    public function getValue(): ?Date
    {
        $value = parent::getValue();

        if ($value === NULL) {
            return NULL;
        }

        return Date::instance($value);
    }

}
