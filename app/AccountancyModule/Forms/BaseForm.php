<?php

namespace App\Forms;

use Nette;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\CsrfProtection;

class BaseForm extends Form
{
    use ContainerTrait;

    /** @var CsrfProtection */
    private $protection;

    public function __construct(Nette\ComponentModel\IContainer $parent = NULL, $name = NULL)
    {
        parent::__construct($parent, $name);
        $this->protection = parent::addProtection('Vypršela platnost formuláře, zkus to ještě jednou.');
    }

    /**
     * @deprecated CSRF protection is auto-enabled for all forms
     * @param string|NULL $message
     * @return CsrfProtection
     */
    public function addProtection($message = NULL) : CsrfProtection
    {
        return $this->protection;
    }

}
