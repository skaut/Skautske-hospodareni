<?php

namespace App\Forms;

use DependentSelectBox\DependentSelectBox;
use DependentSelectBox\JsonDependentSelectBox;
use Nette\Application\UI\Form;
use Nette\InvalidStateException;
use Nextras\Forms\Controls\DatePicker;

trait ContainerTrait
{

    public function addDatePicker(string $name, string $label = NULL): DatePicker
    {
        return $this[$name] = new DatePicker($label);
    }

    public function addContainer($name) : BaseContainer
    {
        $control = new BaseContainer();
        $control->currentGroup = $this->currentGroup;
        return $this[$name] = $control;
    }

    public function addJSelect(string $name, string $label, $parents, $dataCallback) : JsonDependentSelectBox
    {
        $this->checkPresenter();
        return $this[$name] = new JsonDependentSelectBox($label, $parents, $dataCallback);
    }

    public function addDependentSelectBox(string $name, string $label, $parents, $dataCallback) : DependentSelectBox
    {
        $this->checkPresenter();
        return $this[$name] = new DependentSelectBox($label, $parents, $dataCallback);
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
