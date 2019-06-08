<?php

declare(strict_types=1);

namespace App\AccountancyModule\TravelModule\Components;

use App\AccountancyModule\Components\BaseControl;
use App\Forms\BaseForm;
use eGen\MessageBus\Bus\CommandBus;
use eGen\MessageBus\Bus\QueryBus;
use Model\Common\IScanStorage;
use Model\Travel\Commands\Vehicle\AddRoadworthyScan;
use Model\Travel\ReadModel\Queries\Vehicle\RoadworthyScansQuery;
use Nette\Http\FileUpload;
use function array_keys;
use function assert;
use function implode;

final class RoadworthyControl extends BaseControl
{
    /** @var int */
    private $vehicleId;

    /** @var CommandBus */
    private $commandBus;

    /** @var QueryBus */
    private $queryBus;

    public function __construct(int $vehicleId, CommandBus $commandBus, QueryBus $queryBus)
    {
        parent::__construct();
        $this->vehicleId  = $vehicleId;
        $this->commandBus = $commandBus;
        $this->queryBus   = $queryBus;
    }

    public function render() : void
    {
        $this->template->setParameters([
            'files' => $this->queryBus->handle(new RoadworthyScansQuery($this->vehicleId)),
        ]);

        $this->template->getLatte()->addProvider('formsStack', [$this['uploadForm']]);

        $this->template->setFile(__DIR__ . '/templates/RoadworthyControl.latte');
        $this->template->render();
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
}
