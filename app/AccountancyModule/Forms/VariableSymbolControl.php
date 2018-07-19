<?php

declare(strict_types=1);

namespace App\Forms;

use Model\Payment\VariableSymbol;
use Nette\Forms\Controls\TextInput;
use Nette\Forms\Form;

class VariableSymbolControl extends TextInput
{
    /**
     * {@inheritDoc}
     */
    public function __construct($label = null)
    {
        parent::__construct($label, 10);
        $this->addRule(Form::PATTERN, 'Variabilní symbol musí být nejvýše 10 číslic a nezačínat nulou', VariableSymbol::PATTERN);
    }

    public function getValue() : ?VariableSymbol
    {
        $value = parent::getValue();

        if ($value === null || $value === '') {
            return null;
        }
        return new VariableSymbol($value);
    }
}
