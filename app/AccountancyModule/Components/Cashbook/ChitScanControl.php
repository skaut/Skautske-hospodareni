<?php

declare(strict_types=1);

namespace App\AccountancyModule\Components\Cashbook;

use App\AccountancyModule\Components\BaseControl;
use App\Forms\BaseForm;
use eGen\MessageBus\Bus\CommandBus;
use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Commands\Cashbook\AddChitScan;
use Model\Cashbook\Commands\Cashbook\RemoveChitScan;
use Model\Cashbook\ReadModel\Queries\ChitQuery;
use Model\Cashbook\ReadModel\Queries\ChitScansQuery;
use Model\Common\FilePath;
use Model\Common\IScanStorage;
use Model\Common\ScanNotFound;
use Model\DTO\Cashbook\Chit;
use Nette\Http\FileUpload;
use function array_keys;
use function assert;
use function implode;

final class ChitScanControl extends BaseControl
{
    public $chitId;

    /** @var CashbookId */
    private $cashbookId;

    /** @var bool */
    private $isEditable;

    /** @var CommandBus */
    private $commandBus;

    /** @var QueryBus */
    private $queryBus;

    public function __construct(CashbookId $cashbookId, bool $isEditable, CommandBus $commandBus, QueryBus $queryBus)
    {
        parent::__construct();
        $this->cashbookId = $cashbookId;
        $this->isEditable = $isEditable;
        $this->commandBus = $commandBus;
        $this->queryBus   = $queryBus;
    }

    public function render() : void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/ChitScanControl.latte');

        if ($this->chitId === null || ! $this->isEditable) {
            $template->setParameters(['renderModal' => false]);
            $template->render();

            return;
        }

        $chit = $this->queryBus->handle(new ChitQuery($this->cashbookId, (int) $this->chitId));
        assert($chit instanceof Chit);

        $template->setParameters([
            'renderModal' => true,
            'cashbookId' => $this->cashbookId->toString(),
            'chitId' => $this->chitId,
            'isChitEditable' => $this->isChitEditable(),
            'files' => $this->queryBus->handle(new ChitScansQuery($this->cashbookId, (int) $this->chitId)),
        ]);

        $template->getLatte()->addProvider('formsStack', [$this['uploadForm']]);

        $template->render();
    }

    public function handleRemove(string $path) : void
    {
        if (! $this->isChitEditable()) {
            $this->getPresenter()->flashMessage('U pokldního dokladu nyní nelze odebírat naskenované doklady!', 'error');

            return;
        }

        try {
            $this->commandBus->handle(new RemoveChitScan($this->cashbookId, (int) $this->chitId, FilePath::fromString($path)));
            $this->getPresenter()->flashMessage('Sken byl odebrán', 'success');
        } catch (ScanNotFound $e) {
        }

        $this->redrawControl();
    }

    protected function createComponentUploadForm() : BaseForm
    {
        $form = new BaseForm();

        $form->addUpload('scan', 'Další sken')
            ->setRequired('Musíte vybrat sken dokladu')
            ->addRule(
                BaseForm::MIME_TYPE,
                'Neplatný formát skenu, povolené formáty jsou ' . implode(', ', array_keys(IScanStorage::ALLOWED_MIME_TYPES)) . '.',
                IScanStorage::ALLOWED_MIME_TYPES
            )->addRule(BaseForm::MAX_FILE_SIZE, 'Maximální povolená velikost souboru je 15 MB', 15 * 1024 * 1024);

        $form->addSubmit('submit', 'Ok');

        $form->onSuccess[] = function (BaseForm $form) : void {
            $this->formSucceeded($form);
        };

        $form->onSubmit[] = function () : void {
            $this->redrawControl();
        };

        return $form;
    }

    private function formSucceeded(BaseForm $form) : void
    {
        if (! $this->isChitEditable()) {
            $this->getPresenter()->flashMessage('K dokladu nyní nelze přidávat naskenované doklady!', 'error');

            return;
        }

        $upload = $form->getValues()->scan;

        assert($upload instanceof FileUpload);

        if (! $upload->isOk()) {
            $form->addError('Vyskytla se chyba při nahrávání souboru');
        }

        $this->commandBus->handle(
            new AddChitScan($this->cashbookId, (int) $this->chitId, $upload->getSanitizedName(), $upload->getContents())
        );

        $this->getPresenter()->flashMessage('Sken byl nahrán', 'success');
    }

    private function isChitEditable() : bool
    {
        if ($this->chitId === null) {
            return false;
        }
        $chit = $this->queryBus->handle(new ChitQuery($this->cashbookId, (int) $this->chitId));
        assert($chit instanceof Chit);

        return $this->isEditable && ! $chit->isLocked();
    }
}
