<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule\Factories;

use App\AccountancyModule\Components\BaseControl;
use App\Forms\BaseForm;
use Model\Payment\BankAccount\AccountNumber;
use Model\Payment\BankAccountService;
use Model\Payment\InvalidBankAccountNumber;
use Nette\Utils\ArrayHash;

class BankAccountForm extends BaseControl
{
    /** @var int|NULL */
    private $id;

    /** @var BankAccountService */
    private $model;

    public function __construct(?int $id, BankAccountService $model)
    {
        parent::__construct();
        $this->id    = $id;
        $this->model = $model;
    }

    protected function createComponentForm() : BaseForm
    {
        $form = new BaseForm();
        $form->addText('name', 'Název')
            ->setRequired('Musíte vyplnit název');
        $form->addText('prefix')
            ->setRequired(false)
            ->addRule($form::INTEGER, 'Neplatné předčíslí')
            ->addRule($form::MAX_LENGTH, 'Maximální délka předčíslí je %d znaků', 6);
        $form->addText('number')
            ->setRequired('Musíte vyplnit číslo účtu')
            ->addRule($form::INTEGER, 'Neplatné číslo účtu')
            ->addRule($form::MAX_LENGTH, 'Maximální délka čísla účtu je %d znaků', 10);

        $form->addText('bankCode')
            ->setRequired('Musíte vyplnit kód banky')
            ->addRule($form::PATTERN, 'Kod banky musí být 4 číslice', '[0-9]{4}');

        $form->addText('token', 'Token pro párování plateb (FIO)');

        $form->addSubmit('send', 'Uložit');

        if ($this->id !== null) {
            $account = $this->model->find($this->id);
            $form->setDefaults(
                [
                    'name' => $account->getName(),
                    'prefix' => $account->getNumber()->getPrefix(),
                    'number' => $account->getNumber()->getNumber(),
                    'bankCode' => $account->getNumber()->getBankCode(),
                    'token' => $account->getToken(),
                ]
            );
        }

        $form->onSuccess[] = function (BaseForm $form, ArrayHash $values) : void {
            $this->formSucceeded($form, $values);
        };

        return $form;
    }

    private function formSucceeded(BaseForm $form, ArrayHash $values) : void
    {
        try {
            $prefix   = (string) $values->prefix;
            $number   = (string) $values->number;
            $bankCode = (string) $values->bankCode;
            if ($this->id !== null) {
                $this->model->updateBankAccount(
                    $this->id,
                    $values->name,
                    new AccountNumber($prefix, $number, $bankCode),
                    $values->token
                );
            } else {
                $this->model->addBankAccount(
                    $this->getPresenter()->getUnitId(),
                    $values->name,
                    new AccountNumber($prefix, $number, $bankCode),
                    $values->token
                );
            }

            $this->flashMessage('Bankovní účet byl uložen');
            $this->getPresenter()->redirect('BankAccounts:default');
        } catch (InvalidBankAccountNumber $e) {
            $form->addError('Neplatné číslo účtu');
        }
    }

    public function render() : void
    {
        $this->template->setFile(__DIR__ . '/templates/BankAccountForm.latte');
        $this->template->render();
    }
}
