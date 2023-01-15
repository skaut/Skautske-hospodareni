<?php

declare(strict_types=1);

namespace App\Forms;

use Model\Payment\VariableSymbol;
use Nette\Forms\Controls\TextInput;
use Nette\Forms\Form;

class VariableSymbolControl extends TextInput
{
    public function __construct(string $label)
    {
        parent::__construct($label, 10);
        $this->addRule(Form::PATTERN, 'VS musí být nejvýše 10 číslic', '^[0-9]{1,10}$');
        $this->addRule(Form::PATTERN, 'VS nesmí začínat nulou', '^(?!0).*$');
    }

    public function getValue(): VariableSymbol|null
    {
        $value = parent::getValue();

        if ($value === null || $value === '') {
            return null;
        }

        return new VariableSymbol($value);
    }
}
