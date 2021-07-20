<?php

declare(strict_types=1);

namespace App\AccountancyModule\Components\FormControls;

use Cake\Chronos\Date;
use InvalidArgumentException;
use Nette\Forms\Controls\BaseControl;
use Nette\Utils\Html;

use function sprintf;
use function str_replace;

/**
 * Datetime picker with automatic string <-> Date conversion
 */
class DateControl extends BaseControl
{
    private const DATE_FORMAT = 'd.m.Y';

    /**
     * @param mixed $value
     */
    public function setDefaultValue($value): self
    {
        if (! $value instanceof Date && $value !== null) {
            throw new InvalidArgumentException(sprintf('$value must be instance of %s or NULL', Date::class));
        }

        parent::setDefaultValue($value);

        return $this;
    }

    public function disableWeekends(): self
    {
        $this->addRule(
            function (self $control): bool {
                $value = $control->getValue();

                return $value === null || $value->isWeekday();
            },
            'Zadané datum musí být pracovní den',
        );

        $this->getControlPrototype()
            ->setAttribute('data-disable-weekends', 'true');

        return $this;
    }

    public function getValue(): ?Date
    {
        $value = parent::getValue();

        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof Date) {
            return $value;
        }

        return Date::createFromFormat(self::DATE_FORMAT, str_replace(' ', '', $value));
    }

    public function getControl(): Html
    {
        $control = parent::getControl();

        $value = $this->getValue();

        if ($value !== null) {
            $control->setAttribute('value', $value->format(self::DATE_FORMAT));
        }

        $control->setAttribute('autocomplete', 'off');
        $control->setAttribute('class', 'form-control date');

        return $control;
    }
}
