<?php

namespace App\AccountancyModule\PaymentModule\Factories;

use App\Forms\BaseForm;
use Model\Payment\BankAccountService;
use Nette\Application\UI\Control;
use Nette\ArrayHash;

class BankAccountForm extends Control
{

    /** @var int|NULL */
    private $id;

    /** @var BankAccountService */
    private $model;


    public function __construct(?int $id, BankAccountService $model)
    {
        $this->id = $id;
        $this->model = $model;
    }


    protected function createComponentForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addText('name', 'Název')
            ->setRequired('Musíte vyplnit název');
        $form->addText('prefix')
            ->setRequired(FALSE)
            ->addRule($form::INTEGER, 'Neplatné předčíslí')
            ->addRule($form::MAX_LENGTH, 'Maximální délka předčíslí je %d znaků', 6);
        $form->addText('number')
            ->setRequired('Musíte vyplnit číslo účtu')
            ->addRule($form::INTEGER, 'Neplatné číslo účtu')
            ->addRule($form::MAX_LENGTH, 'Maximální délka čísla účtu je %d znaků', 10);

        $form->addText('bankCode')
            ->setRequired('Musíte vyplnit kód banky')
            ->addRule($form::INTEGER, 'Neplatný kód banky')
            ->addRule($form::LENGTH, 'Kód banky má délku %d znaky', 4);

        $form->addText('token', 'Token pro párování plateb (FIO)');

        $form->addSubmit('send', 'Uložit');

        $form->onSuccess[] = function (BaseForm $form, ArrayHash $values) {
            $this->model->addBankAccount(
                $this->getPresenter()->getUnitId(),
                $values->name,
                $values->prefix,
                $values->number,
                $values->bankCode,
                $values->token
            );

            $this->presenter->flashMessage('Bankovní účet byl uložen');
            $this->redirect('this');
        };

        return $form;
    }

    public function render()
    {
        $this->template->setFile(__DIR__ . '/templates/BankAccountForm.latte');
        $this->template->render();
    }

}
