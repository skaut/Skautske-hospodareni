<?php

namespace App\Forms;

use Model\Payment\VariableSymbol;
use Nette\Forms\Controls\TextInput;
use Nette\Forms\Form;

class VariableSymbolControl extends TextInput
{

    public function __construct($label = NULL)
    {
        parent::__construct($label, 10);
        $this->addRule(Form::PATTERN, 'Variabilní symbol musí být nejvýše 10 číslic', VariableSymbol::PATTERN);
    }

    public function getValue(): ?VariableSymbol
    {
        $value = parent::getValue();

        if ($value === NULL || $value === '') {
            return NULL;
        }
        return new VariableSymbol($value);
    }

}
