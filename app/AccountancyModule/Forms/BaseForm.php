<?php

declare(strict_types=1);

namespace App\Forms;

use App\Bootstrap4FormRenderer;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\CsrfProtection;

class BaseForm extends Form
{
    use CustomControlFactories;

    private CsrfProtection $protection;

    public function __construct()
    {
        parent::__construct(null, null);
        $this->setRenderer(new Bootstrap4FormRenderer());
        $this->protection = parent::addProtection('Vypršela platnost formuláře, zkus to ještě jednou.');
    }

    /**
     * @deprecated CSRF protection is auto-enabled for all forms
     */
    public function getProtection() : CsrfProtection
    {
        return $this->protection;
    }
}
