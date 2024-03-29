<?php

declare(strict_types=1);

namespace App\AccountancyModule\Components\FormControls;

use Cake\Chronos\ChronosDate;
use DateTimeImmutable;
use InvalidArgumentException;
use Nette\Forms\Controls\DateTimeControl;
use Nette\Utils\Html;

use function sprintf;
use function str_replace;

/**
 * Datetime picker with automatic string <-> Date conversion
 */
class DateControl extends DateTimeControl
{
    private const DATE_FORMAT = 'd.m.Y';

    public function setDefaultValue(mixed $value): self
    {
        if (! $value instanceof ChronosDate && $value !== null) {
            throw new InvalidArgumentException(sprintf('$value must be instance of %s or NULL', ChronosDate::class));
        }

        parent::setDefaultValue($value?->toNative());

        return $this;
    }

    public function disableWeekends(): self
    {
        $this->addRule(
            function (self $control): bool {
                $value = $control->getValue();

                return $value === null || (new ChronosDate($value))->isWeekday();
            },
            'Zadané datum musí být pracovní den',
        );

        $this->getControlPrototype()
            ->setAttribute('data-disable-weekends', 'true');

        return $this;
    }

    public function getValue(): DateTimeImmutable|int|string|null
    {
        $value = parent::getValue();

        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof DateTimeImmutable) {
            return $value;
        }

        $result = DateTimeImmutable::createFromFormat(self::DATE_FORMAT, str_replace(' ', '', $value));

        return $result === false ? null : $result;
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
        $control->setAttribute('type', 'text');

        return $control;
    }
}
