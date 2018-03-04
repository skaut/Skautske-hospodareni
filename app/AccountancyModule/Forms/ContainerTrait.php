<?php

namespace App\Forms;

use App\AccountancyModule\Components\FormControls\DateControl;
use DependentSelectBox\JsonDependentSelectBox;
use NasExt\Forms\Controls\DependentSelectBox;
use Nette\Application\UI\Form;
use Nette\Forms\IControl;
use Nette\InvalidStateException;
use Nextras\Forms\Controls\DatePicker;

trait ContainerTrait
{

    /**
     * @deprecated Use self::addDate() which handles conversion to Date automatically
     */
    public function addDatePicker(string $name, string $label = NULL): DatePicker
    {
        return $this[$name] = new DatePicker($label);
    }

    public function addDate(string $name, string $label = NULL): DateControl
    {
        return $this[$name] = new DateControl($label);
    }

    public function addVariableSymbol(string $name, string $label): VariableSymbolControl
    {
        return $this[$name] = new VariableSymbolControl($label);
    }

    public function addContainer($name) : BaseContainer
    {
        $control = new BaseContainer();
        $control->currentGroup = $this->currentGroup;
        return $this[$name] = $control;
    }

    /**
     * @deprecated Use addDependentSelectbox which returns superior dependent selectbox implementation
     */
    public function addJSelect(string $name, ?string $label, $parents, callable $dataCallback) : JsonDependentSelectBox
    {
        $this->checkPresenter();
        return $this[$name] = new JsonDependentSelectBox($label, $parents, $dataCallback);
    }

    public function addDependentSelectBox(string $name, ?string $label, IControl ...$parents): DependentSelectBox
    {
        return $this[$name] = new DependentSelectBox($label, $parents);
    }

    private function checkPresenter() : void
    {
        $form = $this->getForm(TRUE);

        if ( ! $form instanceof Form) {
            throw new InvalidStateException('I\'m supposed to be used with ' . Form::class . ' only');
        }

        $form->getPresenter(TRUE);
    }

}
