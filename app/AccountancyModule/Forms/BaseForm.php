<?php

declare(strict_types=1);

namespace App\Forms;

use App\FormRenderer;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\CsrfProtection;

class BaseForm extends Form
{
    use ContainerTrait;

    /** @var CsrfProtection */
    private $protection;

    public function __construct(bool $inline = false)
    {
        parent::__construct(null, null);
        $this->setRenderer(new FormRenderer($inline));
        $this->protection = parent::addProtection('Vypršela platnost formuláře, zkus to ještě jednou.');
    }

    /**
     * @deprecated CSRF protection is auto-enabled for all forms
     */
    public function addProtection($errorMessage = null) : CsrfProtection
    {
        return $this->protection;
    }
}
