<?php

declare(strict_types=1);

namespace App\Components\Payment;

use App\Components\BaseControl;
use App\Components\Dialog;
use App\Model\Bank\Enum\BankTransactionSource;
use App\Model\DTO\Payment\BankAccount as BankAccountDto;
use App\Model\Payment\BankAccountService;
use App\Model\User\UserService;
use Closure;
use Component\Forms\BaseForm;
use InvalidArgumentException;
use Nette\Application\UI\Form;
use Nette\Http\FileUpload;
use Nette\Utils\ArrayHash;

use function assert;
use function in_array;
use function pathinfo;
use function sprintf;
use function strtolower;

use const PATHINFO_EXTENSION;

/** @method void onSuccess(int $bankAccountId) */
final class GpcImportDialog extends Dialog
{
    private const MAX_FILE_SIZE_ERROR = 'Maximální povolená velikost souboru je 15 MB';

    /** @var string[] */
    private const ALLOWED_EXTENSIONS = ['gpc', 'abo', 'txt'];

    /** @var callable[] */
    public array $onSuccess = [];

    /** @persistent */
    public int $bankAccountId = -1;

    /**
     * @param Closure(int): bool $canImportBankAccount
     */
    public function __construct(
        private readonly BankAccountService $bankAccounts,
        private readonly UserService $userService,
        private readonly Closure $canImportBankAccount,
    ) {
    }

    public function handleOpen(int $bankAccountId = -1): void
    {
        try {
            if ($bankAccountId > 0) {
                $this->bankAccountId = $bankAccountId;
            }

            $this->requireImportableAccount();
            $this->show();
        } catch (InvalidArgumentException $e) {
            $this->flashMessage($e->getMessage(), 'danger');
            $this->presenter->redrawControl('flash');
            $this->hide();
        }
    }

    protected function beforeRender(): void
    {
        parent::beforeRender();

        $this->template->setFile(__DIR__.'/templates/GpcImportDialog.latte');
        $this->template->setParameters([
            'account' => $this->currentAccount(),
        ]);
    }

    protected function createComponentForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addUpload('file')
            ->addRule(Form::REQUIRED, 'Vyber GPC soubor k importu.')
            ->addRule(BaseForm::MAX_FILE_SIZE, self::MAX_FILE_SIZE_ERROR, BaseControl::MAX_FILE_SIZE_VALUE);

        $form->addSubmit('send', 'Importovat GPC soubor')
            ->setHtmlAttribute('class', 'ajax btn btn-primary');

        $form->onValidate[] = function (BaseForm $form, ArrayHash $values): void {
            if (! $this->canValidateUpload($form, $values)) {
                return;
            }

            try {
                $this->requireImportableAccount();
            } catch (InvalidArgumentException $e) {
                $form->addError($e->getMessage());
            }

            $this->validateFileExtension($form, $values->file);
        };

        $form->onSubmit[] = function (BaseForm $form): void {
            if (! $form->hasErrors()) {
                return;
            }

            $this->flashErrorsAndClose($form);
        };

        $form->onSuccess[] = function (Form $form): void {
            $this->importSubmitted($form);
        };

        return $form;
    }

    private function importSubmitted(Form $form): void
    {
        $values = $form->getValues();
        $upload = $values->file;
        assert($upload instanceof FileUpload);

        try {
            $batch = $this->bankAccounts->importGpcTransactions(
                $this->requireImportableAccount()->getId(),
                $upload,
                (string) $this->userService->getUserDetail()->Person,
            );

            $this->flashMessage(
                sprintf(
                    'GPC import dokončen: %d transakcí, z toho %d nových.',
                    $batch->getTransactionCount(),
                    $batch->getNewTransactionCount(),
                ),
                'success',
            );
            $this->presenter->redrawControl('flash');
            $this->hide();

            foreach ($this->onSuccess as $callback) {
                $callback($this->bankAccountId);
            }
        } catch (InvalidArgumentException $e) {
            $form->addError($e->getMessage());
        }

        if ($form->hasErrors()) {
            $this->flashErrorsAndClose($form);
        }
    }

    private function flashErrorsAndClose(Form $form): void
    {
        foreach ($form->getErrors() as $error) {
            $this->flashMessage($error, 'danger');
        }

        $this->presenter->redrawControl('flash');
        $this->hide();
    }

    private function validateFileExtension(BaseForm $form, FileUpload $upload): void
    {
        if (! $upload->isOk()) {
            return;
        }

        $extension = strtolower((string) pathinfo($upload->getSanitizedName(), PATHINFO_EXTENSION));

        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            $form->addError('Neplatný formát GPC souboru. Povolené přípony jsou .gpc, .abo a .txt.');
        }
    }

    private function canValidateUpload(BaseForm $form, ArrayHash $values): bool
    {
        return $form->isSubmitted() === $form['send'] && $values->file instanceof FileUpload;
    }

    private function currentAccount(): ?BankAccountDto
    {
        if ($this->bankAccountId <= 0) {
            return null;
        }

        return $this->bankAccounts->find($this->bankAccountId);
    }

    private function requireImportableAccount(): BankAccountDto
    {
        $account = $this->currentAccount();

        if ($account === null) {
            throw new InvalidArgumentException('Bankovní účet neexistuje.');
        }

        if (! ($this->canImportBankAccount)($account->getId())) {
            throw new InvalidArgumentException('Nemáš oprávnění importovat GPC soubor k tomuto účtu.');
        }

        if ($account->getTransactionSource()->value !== BankTransactionSource::GPC->value) {
            throw new InvalidArgumentException('GPC import je dostupný pouze pro účty se zdrojem transakcí GPC.');
        }

        return $account;
    }
}
