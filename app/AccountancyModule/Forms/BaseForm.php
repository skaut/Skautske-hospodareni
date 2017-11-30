<?php

namespace App\Forms;

use App\FormRenderer;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\CsrfProtection;

class BaseForm extends Form
{
    use ContainerTrait;

    /** @var CsrfProtection */
    private $protection;

    public function __construct(bool $inline = FALSE)
    {
        parent::__construct(NULL, NULL);
        $this->setRenderer(new FormRenderer($inline));
        $this->protection = parent::addProtection('Vypršela platnost formuláře, zkus to ještě jednou.');
    }

    /**
     * @deprecated CSRF protection is auto-enabled for all forms
     * @param string|NULL $message
     */
    public function addProtection($message = NULL) : CsrfProtection
    {
        return $this->protection;
    }

}
