<?php

declare(strict_types=1);

namespace Component\Forms;

use App\AccountancyModule\Components\FormControls\DateControl;
use App\AccountancyModule\Components\FormControls\YearSelectControl;
use Kdyby\Replicator\Container;
use NasExt\Forms\Controls\DependentSelectBox;
use Nette\Forms\Control;

trait CustomControlFactories
{
    public function addDate(string $name, $label = null): DateControl
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

    public function addDependentSelectBox(string $name, ?string $label, Control ...$parents): DependentSelectBox
    {
        return $this[$name] = new DependentSelectBox($label, $parents);
    }

    public function addDynamic(string $name, callable $factory, int $createDefault = 0, bool $forceDefault = false): Container
    {
        $control = new Container($factory, $createDefault, $forceDefault);
        $control->currentGroup = $this->currentGroup;

        return $this[$name] = $control;
    }

    public function addYearSelect(string $name, ?string $label = 'Rok', ?callable $filterCallback = null): YearSelectControl
    {
        return $this[$name] = new YearSelectControl($label, $filterCallback);
    }
}
