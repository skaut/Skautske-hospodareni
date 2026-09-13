<?php

declare(strict_types=1);

namespace App\Components\Travel;

use App\Components\BaseControl;
use App\Model\Common\FilePath;
use App\Model\Common\IScanStorage;
use App\Model\Common\ScanNotFound;
use App\Model\Common\Services\CommandBus;
use App\Model\Common\Services\QueryBus;
use App\Model\Travel\Commands\Vehicle\AddRoadworthyScan;
use App\Model\Travel\Commands\Vehicle\RemoveRoadworthyScan;
use App\Model\Travel\ReadModel\Queries\Vehicle\RoadworthyScansQuery;
use Component\Forms\BaseForm;
use LogicException;
use Nette\Application\BadRequestException;
use Nette\Http\FileUpload;
use Nette\Http\IResponse;

use function array_keys;
use function array_values;
use function implode;

final class RoadworthyControl extends BaseControl
{
    public function __construct(private int $vehicleId, private bool $isEditable, private CommandBus $commandBus, private QueryBus $queryBus)
    {
    }

    public function render(): void
    {
        $this->template->setParameters([
            'files' => $this->queryBus->handle(new RoadworthyScansQuery($this->vehicleId)),
            'vehicleId' => $this->vehicleId,
            'isEditable' => $this->isEditable,
        ]);

        $this->template->getLatte()->addProvider('formsStack', [$this['uploadForm']]);

        $this->template->setFile(__DIR__.'/templates/RoadworthyControl.latte');
        $this->template->render();
    }

    public function handleRemove(string $path): void
    {
        $this->assertIsEditable();

        try {
            $this->commandBus->handle(new RemoveRoadworthyScan($this->vehicleId, FilePath::fromString($path)));
            $this->presenter->flashMessage('Sken byl odebrán', 'success');
        } catch (ScanNotFound) {
        }

        $this->redrawControl();
    }

    protected function createComponentUploadForm(): BaseForm
    {
        $form = new BaseForm();

        $form->addUpload('scan', 'Sken')
            ->setRequired('Musíte vybrat sken technického průkazu')
            ->addRule(
                BaseForm::MIME_TYPE,
                'Neplatný formát skenu, povolené formáty jsou '.implode(', ', array_keys(IScanStorage::ALLOWED_MIME_TYPES)).'.',
                array_values(IScanStorage::ALLOWED_MIME_TYPES),
            )
            ->addRule(BaseForm::MAX_FILE_SIZE, 'Maximální povolená velikost souboru je 15 MB', BaseControl::MAX_FILE_SIZE_VALUE);

        $form->addSubmit('submit', 'Ok');

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
        $this->assertIsEditable();

        $upload = $form->getValues(\Nette\Utils\ArrayHash::class)->scan;

        if (! $upload instanceof FileUpload) {
            throw new LogicException('Assertion failed.');
        }
        if (! $upload->isOk()) {
            $form->addError('Vyskytla se chyba při nahrávání souboru');
        }

        $this->commandBus->handle(
            new AddRoadworthyScan($this->vehicleId, $upload->getSanitizedName(), $upload->getContents()),
        );

        $this->presenter->flashMessage('Sken byl nahrán', 'success');
    }

    private function assertIsEditable(): void
    {
        if ($this->isEditable) {
            return;
        }

        throw new BadRequestException('Nemáte oprávnění upravovat vozidlo', IResponse::S403_Forbidden);
    }
}
