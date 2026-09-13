<?php

declare(strict_types=1);

namespace Component\Forms;

use Contributte\FormMultiplier\Multiplier;
use Stringable;

trait CustomControlFactories
{
    public function addDate(string $name, string|Stringable|null $label = null): DateControl
    {
        return $this[$name] = new DateControl($label);
    }

    public function addVariableSymbol(string $name, string $label): VariableSymbolControl
    {
        return $this[$name] = new VariableSymbolControl($label);
    }

    /** @phpstan-param string|int $name */
    public function addContainer($name): BaseContainer
    {
        $control = new BaseContainer();
        $control->currentGroup = $this->currentGroup;

        return $this[$name] = $control;
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
