<?php

namespace App\AccountancyModule\Factories;

use App\Forms\BaseForm;
use App\FormRenderer;

class FormFactory
{

    public function create($inline = FALSE) : BaseForm
    {
        $form = new BaseForm();
        $form->setRenderer(new FormRenderer($inline));

        return $form;
    }

}
