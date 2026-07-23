<?php

declare(strict_types=1);

namespace App\Components\Payment;

use App\Components\BaseControl;
use App\Model\Bank\Entity\BankAccount;
use App\Model\Bank\Enum\BankTransactionSource;
use App\Model\Bank\Services\FioTokenValidatorInterface;
use App\Model\Common\Embeddable\AccountNumber;
use App\Model\Common\Services\CommandBus;
use App\Model\Payment\BankAccountNotFound;
use App\Model\Payment\BankAccountService;
use App\Model\Payment\BankTransactionSourceChangeNotAllowed;
use App\Model\Payment\Commands\BankAccount\CreateBankAccount;
use App\Model\Payment\InvalidBankAccountNumber;
use Component\Forms\BaseForm;
use InvalidArgumentException;
use Nette\Utils\ArrayHash;
use RuntimeException;
use Utility\Cnb\BankNotFoundException;

class BankAccountForm extends BaseControl
{
    public function __construct(
        private readonly ?int $id,
        private readonly BankAccountService $model,
        private readonly CommandBus $commandBus,
        private readonly FioTokenValidatorInterface $fioTokenValidator,
    ) {
    }

    public function render(): void
    {
        $this->template->setFile(__DIR__.'/templates/BankAccountForm.latte');
        $this->template->setParameters([
            'bankCodes' => $this->model->getCzechBankAccountNames(),
            'bankBic' => $this->model->getCzechBankAccountBic(),
            'fioBankCode' => BankAccount::FIO_BANK_CODE,
            'fioSourceValue' => BankTransactionSource::FIO->value,
            'gpcSourceValue' => BankTransactionSource::GPC->value,
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
            ->addRule($form::MAX_LENGTH, 'Maximální délka předčíslí je %d znaků', 6)
            ->setHtmlAttribute('inputmode', 'numeric')
            ->setHtmlAttribute('pattern', '[0-9]*')
            ->setHtmlAttribute('maxlength', 6);
        $form->addText('number')
            ->setRequired('Musíte vyplnit číslo účtu')
            ->addRule($form::INTEGER, 'Neplatné číslo účtu')
            ->addRule($form::MAX_LENGTH, 'Maximální délka čísla účtu je %d znaků', 10)
            ->setHtmlAttribute('inputmode', 'numeric')
            ->setHtmlAttribute('pattern', '[0-9]*')
            ->setHtmlAttribute('maxlength', 10);

        $form->addSelect('bankCode', 'Kód banky', $this->model->getCzechBankAccountCodes())
            ->setRequired('Musíte vyplnit kód banky')
            ->setHtmlAttribute('id', 'bankCode')
            ->setHtmlAttribute('onchange', 'updateInfo(this)')
            ->addRule($form::PATTERN, 'Kód banky musí být 4 číslice', '[0-9]{4}');

        $form->addSelect('transactionSource', 'Zdroj transakcí', BankTransactionSource::toSelect())
            ->setRequired('Musíte vybrat zdroj transakcí')
            ->setDefaultValue(BankTransactionSource::GPC->value)
            ->setHtmlAttribute('id', 'transactionSource')
            ->setHtmlAttribute('onchange', 'updateTransactionSource(this)');

        $form->addText('bankName', 'Název banky')
            ->setHtmlAttribute('id', 'bankName')
            ->setDisabled();
        $form->addText('iban', 'IBAN');
        $form->addText('bic', 'BIC')
            ->setHtmlAttribute('id', 'bankBic')
            ->setDisabled();

        $form->addText('token', 'Token pro párování plateb (FIO)')
            ->setHtmlAttribute('id', 'token');
        $form->addSubmit('send', 'Uložit');

        $form->onValidate[] = function (BaseForm $form, ArrayHash $values): void {
            if ($form->isSubmitted() !== $form['send']) {
                return;
            }

            if (! AccountNumber::validateParts(
                $values->prefix === '' ? null : (string) $values->prefix,
                (string) $values->number,
                (string) $values->bankCode,
            )) {
                $form->addError('Neplatné číslo účtu');
            }

            $bankCode = (string) $values->bankCode;
            $transactionSource = (string) $values->transactionSource;

            if ($transactionSource === BankTransactionSource::FIO->value && $bankCode !== BankAccount::FIO_BANK_CODE) {
                $form->addError('Zdroj FIO API lze použít pouze pro účty vedené u FIO banky.');
            }
        };

        if ($this->id !== null) {
            $account = $this->model->find($this->id) ?? throw new RuntimeException('Bankovní účet nebyl nalezen.');
            $form->setDefaults([
                'name' => $account->getName(),
                'prefix' => $account->getNumber()->getPrefix(),
                'number' => $account->getNumber()->getNumber(),
                'bankCode' => $account->getNumber()->getBankCode(),
                'token' => $account->getToken(),
                'transactionSource' => $account->getTransactionSource()->value,
                'bankName' => $account->getNumber()->getBankName(),
                'iban' => $account->getNumber()->getIban(),
                'bic' => $account->getNumber()->getBic(),
            ]);
        }

        $form->onSuccess[] = function (BaseForm $form, ArrayHash $values): void {
            if ($form->isSubmitted() !== $form['send']) {
                return;
            }

            $this->formSucceeded($form, $values);
        };

        return $form;
    }

    private function formSucceeded(BaseForm $form, ArrayHash $values): void
    {
        try {
            $prefix = $values->prefix === '' ? null : (string) $values->prefix;
            $number = (string) $values->number;
            $bankCode = (string) $values->bankCode;
            $bankInfo = $this->model->getBankInfo($bankCode);
            $accountNumber = new AccountNumber($prefix, $number, $bankCode, $bankInfo->getName(), $values->iban, $bankInfo->getBic());
            $transactionSource = $this->resolveTransactionSource($bankCode, (string) $values->transactionSource);
            $token = $this->resolveToken($transactionSource, $values->token);
            $this->validateFioTokenIfNeeded($accountNumber, $transactionSource, $token);

            if ($this->id !== null) {
                $this->model->updateBankAccount(
                    $this->id,
                    $values->name,
                    $accountNumber,
                    $token,
                    $transactionSource,
                );
            } else {
                $this->commandBus->handle(
                    new CreateBankAccount(
                        $this->getPresenter()->getUnitId(),
                        $values->name,
                        $accountNumber,
                        $token,
                        $transactionSource,
                    ),
                );
            }

            $this->flashMessage('Bankovní účet byl uložen');
            $this->getPresenter()->redirect('BankAccounts:default', ['unitId' => $this->getPresenter()->getUnitId()]);
        } catch (InvalidBankAccountNumber) {
            $form->addError('Neplatné číslo účtu');
        } catch (BankNotFoundException) {
            $form->addError('Banka nebyla podle kódu nalezena');
        } catch (BankAccountNotFound) {
            $form->addError('Bankovní účet nebyl nalezen');
        } catch (BankTransactionSourceChangeNotAllowed|InvalidArgumentException $e) {
            $form->addError($e->getMessage());
        }
    }

    private function resolveTransactionSource(string $bankCode, string $submittedSource): BankTransactionSource
    {
        if ($bankCode !== BankAccount::FIO_BANK_CODE) {
            return BankTransactionSource::GPC;
        }

        return BankTransactionSource::from($submittedSource);
    }

    private function resolveToken(BankTransactionSource $transactionSource, mixed $submittedToken): ?string
    {
        if ($transactionSource->value !== BankTransactionSource::FIO->value) {
            return null;
        }

        $token = trim((string) $submittedToken);

        return $token === '' ? null : $token;
    }

    private function validateFioTokenIfNeeded(AccountNumber $accountNumber, BankTransactionSource $transactionSource, ?string $token): void
    {
        if ($transactionSource->value !== BankTransactionSource::FIO->value || $token === null) {
            return;
        }

        if ($this->id !== null) {
            $account = $this->model->find($this->id);
            if (
                $account !== null
                && $account->getTransactionSource()->value === BankTransactionSource::FIO->value
                && $account->getToken() === $token
            ) {
                return;
            }
        }

        $this->fioTokenValidator->validate($accountNumber, $token);
    }
}
