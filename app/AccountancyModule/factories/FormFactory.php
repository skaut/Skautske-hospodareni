<?php

namespace App\AccountancyModule\Factories;

use App\FormRenderer;
use Nette\Application\UI\Form;

class FormFactory
{

    /**
     * @return Form
     */
    public function create($inline = FALSE)
    {
        $form = new Form();
        $form->setRenderer(new FormRenderer($inline));

        return $form;
    }

}
