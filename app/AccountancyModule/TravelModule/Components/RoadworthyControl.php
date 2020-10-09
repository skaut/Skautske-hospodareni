<?php

declare(strict_types=1);

namespace App\AccountancyModule\TravelModule\Components;

use App\AccountancyModule\Components\BaseControl;
use App\Forms\BaseForm;
use eGen\MessageBus\Bus\CommandBus;
use eGen\MessageBus\Bus\QueryBus;
use Model\Common\FilePath;
use Model\Common\IScanStorage;
use Model\Common\ScanNotFound;
use Model\Travel\Commands\Vehicle\AddRoadworthyScan;
use Model\Travel\Commands\Vehicle\RemoveRoadworthyScan;
use Model\Travel\ReadModel\Queries\Vehicle\RoadworthyScansQuery;
use Model\Travel\Vehicle\RoadworthyScan;
use Nette\Application\BadRequestException;
use Nette\Http\FileUpload;
use Nette\Http\IResponse;
use function array_keys;
use function assert;
use function implode;

final class RoadworthyControl extends BaseControl
{
    private int $vehicleId;

    private bool $isEditable;

    private CommandBus $commandBus;

    private QueryBus $queryBus;

    public function __construct(int $vehicleId, bool $isEditable, CommandBus $commandBus, QueryBus $queryBus)
    {
        parent::__construct();
        $this->vehicleId  = $vehicleId;
        $this->isEditable = $isEditable;
        $this->commandBus = $commandBus;
        $this->queryBus   = $queryBus;
    }

    public function render() : void
    {
        $this->template->setParameters([
            'files' => $this->queryBus->handle(new RoadworthyScansQuery($this->vehicleId)),
            'vehicleId' => $this->vehicleId,
            'isEditable' => $this->isEditable,
        ]);

        $this->template->getLatte()->addProvider('formsStack', [$this['uploadForm']]);

        $this->template->setFile(__DIR__ . '/templates/RoadworthyControl.latte');
        $this->template->render();
    }

    public function handleRemove(string $path) : void
    {
        $this->assertIsEditable();

        try {
            $this->commandBus->handle(new RemoveRoadworthyScan($this->vehicleId, FilePath::generate(RoadworthyScan::FILE_PATH_PREFIX, $path)));
            $this->presenter->flashMessage('Sken byl odebrán', 'success');
        } catch (ScanNotFound $e) {
        }

        $this->redrawControl();
    }

    protected function createComponentUploadForm() : BaseForm
    {
        $form = new BaseForm();

        $form->addUpload('scan', 'Sken')
            ->setRequired('Musíte vybrat sken technického průkazu')
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
        $this->assertIsEditable();

        $upload = $form->getValues()->scan;

        assert($upload instanceof FileUpload);

        if (! $upload->isOk()) {
            $form->addError('Vyskytla se chyba při nahrávání souboru');
        }

        $this->commandBus->handle(
            new AddRoadworthyScan($this->vehicleId, $upload->getSanitizedName(), $upload->getContents())
        );

        $this->presenter->flashMessage('Sken byl nahrán', 'success');
    }

    private function assertIsEditable() : void
    {
        if ($this->isEditable) {
            return;
        }

        throw new BadRequestException('Nemáte oprávnění upravovat vozidlo', IResponse::S403_FORBIDDEN);
    }
}
