<?php

declare(strict_types=1);

namespace Component\Forms;

use Contributte\FormMultiplier\Multiplier;
use Nette\Forms\Controls\TextInput;
use Nette\Forms\Form;
use Stringable;

trait CustomControlFactories
{
    public function addDate(string $name, string|Stringable|null $label = null): DateControl
    {
        return $this[$name] = new DateControl($label);
    }

    /** Text input for a positive decimal number with up to 2 decimals; accepts both comma and dot. */
    public function addDecimal(string $name, string $label, string $message): TextInput
    {
        return $this->addText($name, $label)
            ->setHtmlAttribute('inputmode', 'decimal')
            ->setHtmlAttribute('pattern', '\d+([,.]\d{1,2})?')
            ->setHtmlAttribute('data-decimal', true)
            ->addRule(Form::FLOAT, $message);
    }

    public function addVariableSymbol(string $name, string $label): VariableSymbolControl
    {
        return $this[$name] = new VariableSymbolControl($label);
    }

    public function addEmailList(string $name, string $label, string $fallbackLabel): EmailListControl
    {
        return $this[$name] = new EmailListControl($label, $fallbackLabel);
    }

    /** @phpstan-param string|int $name */
    public function addContainer($name): BaseContainer
    {
        $control = new BaseContainer();
        $control->currentGroup = $this->currentGroup;
        $this[(string) $name] = $control;

        return $control;
    }

    public function addDynamic(string $name, callable $factory, int $createDefault = 0, bool $forceDefault = false): Multiplier
    {
        $control = new Multiplier($factory, $createDefault);
        $control->currentGroup = $this->currentGroup;

        return $this[$name] = $control;
    }

    public function addYearSelect(string $name, ?string $label = 'Rok', ?callable $filterCallback = null): YearSelectControl
    {
        return $this[$name] = new YearSelectControl($label, $filterCallback);
    }
}
