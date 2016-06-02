<?php

namespace App\AccountancyModule\Factories;

use Nette\Application\UI\Form;
use Nextras\Forms\Rendering\Bs3FormRenderer;

class FormFactory
{

	/**
	 * @return Form
	 */
	public function create($inline = FALSE)
	{
		$form = new Form();
		$form->setRenderer(new Bs3FormRenderer());

		if($inline) {
			$form->getElementPrototype()->setClass("form-inline");
		}

		return $form;
	}

}