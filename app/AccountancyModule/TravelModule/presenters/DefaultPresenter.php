<?php

declare(strict_types=1);

namespace App\AccountancyModule\TravelModule;

use App\AccountancyModule\TravelModule\Components\CommandGrid;
use App\AccountancyModule\TravelModule\Components\EditTravelDialog;
use App\AccountancyModule\TravelModule\Factories\ICommandGridFactory;
use App\AccountancyModule\TravelModule\Factories\IEditTravelDialogFactory;
use App\Forms\BaseForm;
use Assert\Assertion;
use Model\BaseService;
use Model\Services\PdfRenderer;
use Model\Travel\Travel\TransportType;
use Model\TravelService;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Forms\Controls\SelectBox;
use Nette\Http\IResponse;
use Nette\Security\Identity;
use function array_key_exists;
use function array_map;
use function array_slice;
use function assert;
use function count;
use function round;
use function sprintf;
use function str_replace;

class DefaultPresenter extends BasePresenter
{
    /** @var int|null */
    private $commandId;

    /** @var TravelService */
    private $travelService;

    /** @var PdfRenderer */
    private $pdf;

    /** @var ICommandGridFactory */
    private $gridFactory;

    /** @var IEditTravelDialogFactory */
    private $editTravelDialogFactory;

    public function __construct(
        TravelService $travelService,
        PdfRenderer $pdf,
        ICommandGridFactory $gridFactory,
        IEditTravelDialogFactory $editTravelDialogFactory
    ) {
        parent::__construct();
        $this->travelService           = $travelService;
        $this->pdf                     = $pdf;
        $this->gridFactory             = $gridFactory;
        $this->editTravelDialogFactory = $editTravelDialogFactory;
        $this->setLayout('layout.new');
    }

    private function isCommandAccessible(int $commandId) : bool
    {
        $command = $this->travelService->getCommandDetail($commandId);
        if ($command === null) {
            return false;
        }
        $identity = $this->getUser()->getIdentity();

        assert($identity instanceof Identity);

        return $command->getOwnerId() === $this->getUser()->getId() ||
            array_key_exists($command->getUnitId(), $identity->access[BaseService::ACCESS_READ]);
    }

    private function isCommandEditable(int $id) : bool
    {
        $command  = $this->travelService->getCommandDetail($id);
        $identity = $this->getUser()->getIdentity();

        assert($identity instanceof Identity);

        $unitOrOwner = $command->getOwnerId() === $this->getUser()->getId() ||
            array_key_exists($command->getUnitId(), $identity->access[BaseService::ACCESS_EDIT]);

        return $this->isCommandAccessible($id) &&
            $command->getClosedAt() === null && $unitOrOwner;
    }

    public function actionDetail(?int $id) : void
    {
        if ($id === null) {
            $this->redirect('default');
        }

        $this->commandId = $id;

        if (! $this->isCommandAccessible($id)) {
            $this->flashMessage('Neoprávněný přístup k záznamu!', 'danger');
            $this->redirect('default');
        }

        $command = $this->travelService->getCommandDetail($id);

        $form = $this['formAddTravel'];

        $this->getTypeSelectBox($form)->setItems($command->getTransportTypePairs());
        $form->setDefaults(['command_id' => $id]);
    }

    public function renderDetail(int $id) : void
    {
        $command = $this->travelService->getCommandDetail($id);
        $vehicle = $command->getVehicleId() !== null
            ? $this->travelService->getVehicleDTO($command->getVehicleId())
            : null;

        $this->template->setParameters([
            'command'    => $command,
            'vehicle'    => $vehicle,
            'types'      => $command->getTransportTypePairs(),
            'isEditable' => $this->isCommandEditable($command->getId()),
            'travels'    => $this->travelService->getTravels($command->getId()),
        ]);
    }

    public function actionPrint(int $commandId) : void
    {
        if (! $this->isCommandAccessible($commandId)) {
            $this->flashMessage('Neoprávněný přístup k záznamu!', 'danger');
            $this->redirect('default');
        }

        $command   = $this->travelService->getCommandDetail($commandId);
        $travels   = $this->travelService->getTravels($commandId);
        $vehicleId = $command->getVehicleId();

        $template = $this->getTemplateFactory()->createTemplate();

        assert($template instanceof Template);

        $template->setParameters([
            'command' => $command,
            'travels' => $travels,
            'types' => array_map(fn(TransportType $t) => $t->getLabel(), $command->getTransportTypes()),
            'vehicle' => $vehicleId !== null ? $this->travelService->findVehicle($vehicleId) : null,
        ]);

        if (count($travels) !== 0) {
            $template->setParameters([
                'start' => $travels[0],
                'end' => array_slice($travels, -1)[0],
            ]);
        }

        $template->getLatte()->addFilter(null, '\\App\\AccountancyModule\\AccountancyHelpers::loader');
        $template->setFile(__DIR__ . '/../templates/Default/ex.command.latte');

        $this->pdf->render((string) $template, 'cestovni-prikaz.pdf');
        $this->terminate();
    }

    public function handleCloseCommand(int $commandId) : void
    {
        if (! $this->isCommandAccessible($commandId)) {
            $this->flashMessage('Nemáte právo uzavřít cestovní příkaz.', 'danger');
            $this->redirect('default');
        }

        $this->travelService->closeCommand($commandId);
        $this->flashMessage('Cestovní příkaz byl uzavřen.');
        $this->redirect('this');
    }

    public function handleOpenCommand(int $commandId) : void
    {
        if (! $this->isCommandAccessible($commandId)) {
            $this->flashMessage('Nemáte právo otevřít cestovní příkaz.', 'danger');
            $this->redirect('default');
        }

        $this->travelService->openCommand($commandId);
        $this->flashMessage('Cestovní příkaz byl otevřen.');
        $this->redirect('this');
    }

    public function handleRemoveTravel(int $commandId, int $travelId) : void
    {
        if (! $this->isCommandEditable($commandId)) {
            $this->flashMessage('Nemáte oprávnění smazat cestu.', 'danger');
            $this->redirect('this');
        }

        $this->travelService->removeTravel($commandId, $travelId);
        $this->flashMessage('Cesta byla smazána.');

        $this->redirect('this');
    }

    public function handleRemoveCommand(int $commandId) : void
    {
        if (! $this->isCommandAccessible($commandId)) {
            $this->flashMessage('Nemáte právo upravovat záznam.', 'danger');
            $this->redirect('default');
        }

        $this->travelService->deleteCommand($commandId);
        $this->flashMessage('Cestovní příkaz byl smazán.');
        $this->redirect('default');
    }

    protected function createComponentFormAddTravel() : BaseForm
    {
        $form = new BaseForm();
        $form->useBootstrap4();
        $form->addHidden('command_id');
        $form->addSelect('type');
        $form->addDate('start_date', 'Datum cesty')
            ->setAttribute('class', 'date')
            ->addRule(Form::FILLED, 'Musíte vyplnit datum cesty.');
        $form->addText('start_place', 'Z*')
            ->addRule(Form::FILLED, 'Musíte vyplnit místo počátku cesty.');
        $form->addText('end_place', 'Do*')
            ->addRule(Form::FILLED, 'Musíte vyplnit místo konce cesty.');
        $form->addText('distance', 'Vzdálenost*')
            ->addRule(Form::FILLED, 'Musíte vyplnit vzdálenost.')
            ->addRule(Form::FLOAT, 'Vzdálenost musí být číslo!')
            ->addRule(Form::MIN, 'Vzdálenost musí být větší než 0.', 0.01);
        $form->addSubmit('send', 'Přidat')
            ->setAttribute('class', 'btn btn-primary');

        $form->onSuccess[] = function (Form $form) : void {
            $this->formAddTravelSubmitted($form);
        };

        return $form;
    }

    private function formAddTravelSubmitted(Form $form) : void
    {
        $v         = $form->getValues();
        $commandId = (int) $v->command_id;

        if (! $this->isCommandEditable($commandId)) {
            $this->flashMessage('Nelze upravovat cestovní příkaz.', 'danger');
            $this->redirect('default');
        }
        $v['distance'] = round((float) str_replace(',', '.', $v['distance']), 2);

        $this->travelService->addTravel(
            $commandId,
            TransportType::get($v->type),
            $v->start_date,
            $v->start_place,
            $v->end_place,
            $v->distance
        );
        $this->flashMessage('Cesta byla přidána.');
        $this->redirect('this');
    }

    public function handleEditTravel(int $travelId) : void
    {
        $this['editTravelDialog']->open($travelId);
    }

    protected function createComponentEditTravelDialog() : EditTravelDialog
    {
        $commandId = $this->commandId;

        Assertion::notNull($commandId);

        if (! $this->isCommandEditable($commandId)) {
            throw new BadRequestException(sprintf('User cannot edit command %d', $commandId), IResponse::S403_FORBIDDEN);
        }

        return $this->editTravelDialogFactory->create($commandId);
    }

    protected function createComponentGrid() : CommandGrid
    {
        return $this->gridFactory->create($this->getUnitId(), $this->getUser()->getId());
    }

    private function getTypeSelectBox(BaseForm $form) : SelectBox
    {
        $selectBox = $form['type'];

        assert($selectBox instanceof SelectBox);

        return $selectBox;
    }
}
