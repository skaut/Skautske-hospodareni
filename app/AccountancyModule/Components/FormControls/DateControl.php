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

    public function setDefaultValue($value): self
    {
        if (!$value instanceof Date && $value !== NULL) {
            throw new \InvalidArgumentException(sprintf('$value must be instance of %s or NULL', Date::class));
        }

        parent::setDefaultValue($value);

        return $this;
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
