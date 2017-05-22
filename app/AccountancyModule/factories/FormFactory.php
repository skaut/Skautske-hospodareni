<?php

namespace App\AccountancyModule\Factories;

use App\Forms\BaseForm;
use App\FormRenderer;

/**
 * @deprecated use new BaseForm($inline) directly
 */
class FormFactory
{

    public function create(bool $inline = FALSE) : BaseForm
    {
        return new BaseForm($inline);
    }

}
