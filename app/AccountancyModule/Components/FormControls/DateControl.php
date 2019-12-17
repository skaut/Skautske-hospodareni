<?php

declare(strict_types=1);

namespace App\AccountancyModule\Components\FormControls;

use Cake\Chronos\Date;
use InvalidArgumentException;
use Nette\Utils\Html;
use Nextras\Forms\Controls\DatePicker;
use function sprintf;

/**
 * Datetime picker with automatic string <-> Date conversion
 */
class DateControl extends DatePicker
{
    /**
     * @param mixed $value
     */
    public function setDefaultValue($value) : self
    {
        if (! $value instanceof Date && $value !== null) {
            throw new InvalidArgumentException(sprintf('$value must be instance of %s or NULL', Date::class));
        }

        parent::setDefaultValue($value);

        return $this;
    }

    public function disableWeekends() : self
    {
        $this->addRule(function (self $control) : bool {
            $value = $control->getValue();

            return $value === null || $value->isWeekday();
        });

        $this->getControlPrototype()
            ->setAttribute('data-disable-weekends', 'true');

        return $this;
    }

    public function getValue() : ?Date
    {
        $value = parent::getValue();

        if ($value === null) {
            return null;
        }

        return Date::instance($value);
    }

    public function getControl() : Html
    {
        $control = parent::getControl();

        $value = $this->getValue();

        if ($value !== null) {
            $control->setAttribute('value', $value->format($this->htmlFormat));
        }

        $control->setAttribute('autocomplete', 'off');

        return $control;
    }
}
