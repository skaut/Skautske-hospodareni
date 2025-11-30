<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule\Factories;

use App\AccountancyModule\Components\BaseControl;
use Component\Forms\BaseForm;
use Entity\BankAccount;
use Entity\Embeddable\AccountNumber;
use Model\Common\Services\CommandBus;
use Model\Payment\BankAccountNotFound;
use Model\Payment\BankAccountService;
use Model\Payment\Commands\BankAccount\CreateBankAccount;
use Model\Payment\InvalidBankAccountNumber;
use Nette\Utils\ArrayHash;
use Utility\Cnb\BankNotFoundException;

class BankAccountForm extends BaseControl
{
    public function __construct(
        private readonly ?int $id,
        private readonly BankAccountService $model,
        private readonly CommandBus $commandBus,
    ) {
    }

    public function render(): void
    {
        $this->template->setFile(__DIR__.'/templates/BankAccountForm.latte');
        $this->template->setParameters([
            'bankCodes' => $this->model->getCzechBankAccountNames(),
            'bankBic' => $this->model->getCzechBankAccountBic(),
        ]);
        $this->template->render();
    }

    protected function createComponentForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addText('name', 'Označení účtu')
            ->setRequired('Musíte vyplnit název');
        $form->addText('prefix')
            ->setRequired(false)
            ->addRule($form::INTEGER, 'Neplatné předčíslí')
            ->addRule($form::MAX_LENGTH, 'Maximální délka předčíslí je %d znaků', 6);
        $form->addText('number')
            ->setRequired('Musíte vyplnit číslo účtu')
            ->addRule($form::INTEGER, 'Neplatné číslo účtu')
            ->addRule($form::MAX_LENGTH, 'Maximální délka čísla účtu je %d znaků', 10);

        $form->addSelect('bankCode', 'Kód banky', $this->model->getCzechBankAccountCodes())
            ->setRequired('Musíte vyplnit kód banky')
            ->setHtmlAttribute('id', 'bankCode')
            ->setHtmlAttribute('onchange', 'updateInfo(this)')
            ->addRule($form::PATTERN, 'Kod banky musí být 4 číslice', '[0-9]{4}')
            ->addCondition($form::Equal, BankAccount::FIO_BANK_CODE)
            ->toggle('#api');

        $form->addText('bankName', 'Název banky')
            ->setHtmlAttribute('id', 'bankName')
            ->setDisabled();
        $form->addText('iban', 'IBAN');
        $form->addText('bic', 'BIC')
            ->setHtmlAttribute('id', 'bankBic')
            ->setDisabled();

        $form->addText('token', 'Token pro párování plateb (FIO)');

        $form->addSubmit('send', 'Uložit');

        // EDIT
        if ($this->id !== null) {
            $account = $this->model->find($this->id);
            $form->setDefaults([
                'name' => $account->getName(),
                'prefix' => $account->getNumber()->getPrefix(),
                'number' => $account->getNumber()->getNumber(),
                'bankCode' => $account->getNumber()->getBankCode(),
                'token' => $account->getToken(),
                'bankName' => $account->getNumber()->getBankName(),
                'iban' => $account->getNumber()->getIban(),
                'bic' => $account->getNumber()->getBic(),
            ]);
        }

        $form->onSuccess[] = function (BaseForm $form, ArrayHash $values): void {
            if ($form->isSubmitted() != $form['send']) {
                return;
            }
            $this->formSucceeded($form, $values);
        };

        return $form;
    }

    private function formSucceeded(BaseForm $form, ArrayHash $values): void
    {
        try {
            $bankInfo = $this->model->getBankInfo($values->bankCode);
            $prefix = (string) $values->prefix;
            $number = (string) $values->number;
            $bankCode = (string) $values->bankCode;
            if ($this->id !== null) {
                $this->model->updateBankAccount(
                    $this->id,
                    $values->name,
                    new AccountNumber($prefix, $number, $bankCode, $bankInfo->getName(), $values->iban, $bankInfo->getBic()),
                    $values->token,
                );
            } else {
                $this->commandBus->handle(
                    new CreateBankAccount(
                        $this->getPresenter()->getUnitId(),
                        $values->name,
                        new AccountNumber($prefix, $number, $bankCode, $bankInfo->getName(), $values->iban, $bankInfo->getBic()),
                        $values->token,
                    ),
                );
            }

            $this->flashMessage('Bankovní účet byl uložen');
            $this->getPresenter()->redirect('BankAccounts:default');
        } catch (InvalidBankAccountNumber) {
            $form->addError('Neplatné číslo účtu');
        } catch (BankNotFoundException) {
            $form->addError('Banka nebyla podle kódu nalezena');
        } catch (BankAccountNotFound) {
            $form->addError('Bankovní účet nebyl nalezen');
        }
    }
}
