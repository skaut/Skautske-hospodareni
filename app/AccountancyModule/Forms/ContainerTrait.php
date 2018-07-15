<?php

declare(strict_types=1);

namespace App\Forms;

use App\AccountancyModule\Components\FormControls\DateControl;
use NasExt\Forms\Controls\DependentSelectBox;
use Nette\Forms\IControl;
use Nextras\Forms\Controls\DatePicker;

trait ContainerTrait
{
    /**
     * @deprecated Use self::addDate() which handles conversion to Date automatically
     */
    public function addDatePicker(string $name, ?string $label = null) : DatePicker
    {
        return $this[$name] = new DatePicker($label);
    }

    public function addDate(string $name, ?string $label = null) : DateControl
    {
        return $this[$name] = new DateControl($label);
    }

    public function addVariableSymbol(string $name, string $label) : VariableSymbolControl
    {
        return $this[$name] = new VariableSymbolControl($label);
    }

    public function addContainer($name) : BaseContainer
    {
        $control               = new BaseContainer();
        $control->currentGroup = $this->currentGroup;
        return $this[$name]    = $control;
    }

    public function addDependentSelectBox(string $name, ?string $label, IControl ...$parents) : DependentSelectBox
    {
        return $this[$name] = new DependentSelectBox($label, $parents);
    }
}
