<?php

declare(strict_types=1);

namespace App\Components\Cashbook;

use App\Components\BaseControl;
use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\Commands\Cashbook\AddChitScan;
use App\Model\Cashbook\Commands\Cashbook\RemoveChitScan;
use App\Model\Cashbook\ReadModel\Queries\ChitQuery;
use App\Model\Common\FilePath;
use App\Model\Common\IScanStorage;
use App\Model\Common\ScanNotFound;
use App\Model\Common\Services\CommandBus;
use App\Model\Common\Services\QueryBus;
use App\Model\DTO\Cashbook\Chit;
use Component\Forms\BaseForm;
use LogicException;
use Nette\Http\FileUpload;

use function array_keys;
use function array_values;
use function implode;

final class ChitScanControl extends BaseControl
{
    public function __construct(private CashbookId $cashbookId, private int $chitId, private bool $isEditable, private CommandBus $commandBus, private QueryBus $queryBus)
    {
    }

    public function render(): void
    {
        $template = $this->template;
        $template->setFile(__DIR__.'/templates/ChitScanControl.latte');
        $this['uploadForm']->setDefaults(['chitId' => $this->chitId]);

        $chit = $this->queryBus->handle(new ChitQuery($this->cashbookId, $this->chitId));
        if (! $chit instanceof Chit) {
            throw new LogicException('Assertion failed.');
        }
        $template->setParameters([
            'cashbookId' => $this->cashbookId->toString(),
            'isEditable' => $this->isChitEditable(),
            'chitId' => $chit->getId(),
            'files' => $chit->getScans(),
        ]);

        $template->getLatte()->addProvider('formsStack', [$this['uploadForm']]);

        $template->render();
    }

    public function handleRemove(string $path): void
    {
        if (! $this->isChitEditable()) {
            $this->getPresenter()->flashMessage('U pokladního dokladu nyní nelze odebírat naskenované doklady!', 'error');

            return;
        }

        try {
            $this->commandBus->handle(new RemoveChitScan($this->cashbookId, $this->chitId, FilePath::fromString($path)));
            $this->getPresenter()->flashMessage('Sken byl odebrán', 'success');
        } catch (ScanNotFound) {
        }

        if ($this->getPresenter()->isAjax()) {
            $this->redrawControl();
        } else {
            $this->getPresenter()->redirect('this');
        }
    }

    protected function createComponentUploadForm(): BaseForm
    {
        $form = new BaseForm();

        $form->addHidden('chitId');
        $form->addUpload('scan', 'Další sken')
            ->setRequired('Musíte vybrat sken dokladu')
            ->addRule(
                BaseForm::MIME_TYPE,
                'Neplatný formát skenu, povolené formáty jsou '.implode(', ', array_keys(IScanStorage::ALLOWED_MIME_TYPES)).'.',
                array_values(IScanStorage::ALLOWED_MIME_TYPES),
            )
            ->addRule(BaseForm::MAX_FILE_SIZE, 'Maximální povolená velikost souboru je 15 MB', BaseControl::MAX_FILE_SIZE_VALUE);

        $form->addSubmit('submit', 'Nahrát');

        $form->onSuccess[] = function (BaseForm $form): void {
            $this->formSucceeded($form);
        };

        $form->onSubmit[] = function (): void {
            $this->redrawControl();
        };

        return $form;
    }

    private function formSucceeded(BaseForm $form): void
    {
        $chitId = $form->getValues()->chitId;
        $chitId = $chitId !== null ? (int) $chitId : $chitId;

        if (! $this->isChitReadable()) {
            $this->getPresenter()->flashMessage('Nemáte právo číst doklad!', 'error');
            $this->getPresenter()->redirect('this');

            return;
        }

        $upload = $form->getValues()->scan;

        if (! $upload instanceof FileUpload) {
            throw new LogicException('Assertion failed.');
        }
        if (! $upload->isOk()) {
            $form->addError('Vyskytla se chyba při nahrávání souboru');
        }

        $this->commandBus->handle(
            new AddChitScan($this->cashbookId, (int) $chitId, $upload->getSanitizedName(), $upload->getContents()),
        );

        $this->getPresenter()->flashMessage('Sken byl nahrán', 'success');
        $this->getPresenter()->redirect('this');
    }

    private function isChitEditable(): bool
    {
        $chit = $this->queryBus->handle(new ChitQuery($this->cashbookId, $this->chitId));
        if (! $chit instanceof Chit) {
            throw new LogicException('Assertion failed.');
        }

        return $this->isEditable && ! $chit->isLocked();
    }

    private function isChitReadable(): bool
    {
        $chit = $this->queryBus->handle(new ChitQuery($this->cashbookId, $this->chitId));
        if (! $chit instanceof Chit) {
            throw new LogicException('Assertion failed.');
        }

        return true;
    }
}
