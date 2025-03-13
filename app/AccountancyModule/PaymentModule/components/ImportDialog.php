<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule\Components;

use App\AccountancyModule\Components\BaseControl;
use App\AccountancyModule\Components\Dialog;
use App\Forms\BaseForm;
use InvalidArgumentException;
use Model\Common\Services\CommandBus;
use Model\Payment\Payment\CsvParser;
use Nette\Application\UI\Form;
use Nette\Http\FileUpload;
use Nette\Schema\ValidationException;

use function array_keys;
use function array_values;
use function assert;
use function implode;

/** @method void onSuccess() */
final class ImportDialog extends Dialog
{
    public const ALLOWED_MIME_TYPES = [
        'txt' => 'text/plain',
        'csv' => 'text/csv',
        'xls' => 'application/vnd.ms-excel', // Některé prohlížeče hlásí CSV jako XLS
        'app/csv' => 'application/csv',
    ];

    /** @var callable[] */
    public array $onSuccess = [];

    public function __construct(private int $groupId, private CommandBus $commandBus)
    {
    }

    public function handleOpen(): void
    {
        $this->show();
    }

    protected function beforeRender(): void
    {
        $this->template->setFile(__DIR__ . '/templates/ImportDialog.latte');
    }

    protected function createComponentForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addUpload('file')
            ->addRule(Form::REQUIRED, 'Povinná položka')
            ->addRule(BaseForm::MIME_TYPE, 'Neplatný formát CSV, povolené formáty jsou ' . implode(', ', array_keys(self::ALLOWED_MIME_TYPES)) . '.', array_values(self::ALLOWED_MIME_TYPES))
            ->addRule(BaseForm::MAX_FILE_SIZE, 'Maximální povolená velikost souboru je 15 MB', BaseControl::MAX_FILE_SIZE_VALUE);

        $form->addSubmit('send', 'Importovat položky ze souboru')
            ->setHtmlAttribute('class', 'ajax btn btn-primary');

        $form->onSuccess[] = function (Form $form): void {
            $this->importSubmitted($form);
        };

        $form->onSubmit[] = function (Form $form): void {
            if (! $form->hasErrors()) {
                return;
            }

            $this->redrawControl();
        };

        return $form;
    }

    private function importSubmitted(Form $form): void
    {
        $upload = $form->getValues()->file;
        assert($upload instanceof FileUpload);

        if (! $upload->isOk()) {
            $form->addError('Vyskytla se chyba při nahrávání souboru');
        }

        $csvParser = new CsvParser();

        try {
            $csv = $csvParser->parse($this->groupId, $upload->getContents());
            foreach ($csv as $value) {
                $this->commandBus->handle($value);
            }

            $this->flashMessage('Platby byly importovány', 'success');
            $this->onSuccess();
        } catch (ValidationException | InvalidArgumentException $e) {
            $form->addError($e->getMessage());
        }

        if ($form->hasErrors()) {
            foreach ($form->getErrors() as $error) {
                $this->flashMessage($error, 'danger');
            }
        }

        $this->hide();
    }
}
