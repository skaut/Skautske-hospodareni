<?php

declare(strict_types=1);

namespace App\Components\Payment;

use Component\Forms\BaseContainer;
use Component\Forms\BaseForm;
use Nette\Application\UI\Form;

final class PaymentFormFields
{
    public static function addName(BaseForm|BaseContainer $container, string $label = 'Název'): void
    {
        $container->addText('name', $label)
            ->addRule(Form::FILLED, 'Musíte zadat název platby');
    }

    public static function addAmount(BaseForm|BaseContainer $container, string $label = 'Částka', bool $required = true): void
    {
        $control = $container->addText('amount', $label)
            ->setNullable()
            ->setRequired(false)
            ->addRule(Form::FLOAT, 'Částka musí být zadaná jako číslo')
            ->addRule(Form::MIN, 'Částka musí být větší než 0', 0.01);

        if ($required) {
            $control->addRule(Form::FILLED, 'Musíte vyplnit částku');
        }
    }

    public static function addDueDate(BaseForm|BaseContainer $container, string $label = 'Splatnost', bool $required = true): void
    {
        $control = $container->addDate('dueDate', $label)
            ->disableWeekends();

        if ($required) {
            $control->setRequired('Musíte vyplnit splatnost');
        }
    }

    public static function addVariableSymbol(BaseForm|BaseContainer $container, string $label = 'VS'): void
    {
        $container->addVariableSymbol('variableSymbol', $label)
            ->setRequired(false);
    }

    public static function addConstantSymbol(BaseForm|BaseContainer $container, string $label = 'KS'): void
    {
        $container->addText('constantSymbol', $label)
            ->setNullable()
            ->setMaxLength(4)
            ->setHtmlType('text')
            ->setRequired(false)
            ->addRule(Form::INTEGER, 'KS musí být číslo');
    }

    public static function addNote(BaseForm|BaseContainer $container, string $label = 'Poznámka'): void
    {
        $container->addText('note', $label)
            ->setRequired(false);
    }
}
