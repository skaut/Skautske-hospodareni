<?php

namespace App\Forms;

use DependentSelectBox\DependentSelectBox;
use DependentSelectBox\JsonDependentSelectBox;
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
		/* @var $form \Nette\Application\UI\Form */
		$form = $this->getForm(TRUE);
		$form->getPresenter(TRUE);
	}

}
