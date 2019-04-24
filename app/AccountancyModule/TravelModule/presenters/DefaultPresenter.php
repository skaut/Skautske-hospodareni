<?php

declare(strict_types=1);

namespace App\AccountancyModule\TravelModule;

use App\AccountancyModule\TravelModule\Components\CommandGrid;
use App\AccountancyModule\TravelModule\Factories\ICommandGridFactory;
use App\Forms\BaseForm;
use Model\BaseService;
use Model\DTO\Travel\TravelType;
use Model\Services\PdfRenderer;
use Model\TravelService;
use Model\Utils\MoneyFactory;
use Nette\Application\UI\Form;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Forms\Controls\SelectBox;
use Nette\Security\Identity;
use function array_key_exists;
use function array_map;
use function array_slice;
use function assert;
use function count;
use function round;
use function str_replace;

class DefaultPresenter extends BasePresenter
{
    /** @var TravelService */
    private $travelService;

    /** @var PdfRenderer */
    private $pdf;

    /** @var ICommandGridFactory */
    private $gridFactory;

    public function __construct(TravelService $travelService, PdfRenderer $pdf, ICommandGridFactory $gridFactory)
    {
        parent::__construct();
        $this->travelService = $travelService;
        $this->pdf           = $pdf;
        $this->gridFactory   = $gridFactory;
    }

    private function isCommandAccessible(int $commandId) : bool
    {
        $command  = $this->travelService->getCommandDetail($commandId);
        $identity = $this->getUser()->getIdentity();

        assert($identity instanceof Identity);

        $unitOrOwner = $command->getOwnerId() === $this->getUser()->getId() ||
            array_key_exists($command->getUnitId(), $identity->access[BaseService::ACCESS_READ]);

        return $command !== null && $unitOrOwner;
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
        if (! $this->isCommandAccessible($id)) {
            $this->flashMessage('Neoprávněný přístup k záznamu!', 'danger');
            $this->redirect('default');
        }

        $command = $this->travelService->getCommandDetail($id);

        $this->getTypeSelectBox()->setItems($command->getTransportTypePairs());
        $this['formAddTravel']->setDefaults(['command_id' => $id]);
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

        $template->setParameters(
            [
            'command' => $command,
            'travels' => $travels,
            'types' => array_map(function (TravelType $t) {
                return $t->getLabel();
            }, $command->getTransportTypes()),
            'vehicle' => $vehicleId !== null ? $this->travelService->findVehicle($vehicleId) : null,
            ]
        );

        if (count($travels) !== 0) {
            $template->setParameters(
                [
                'start' => $travels[0],
                'end' => array_slice($travels, -1)[0],
                ]
            );
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
            $this->redirect('default');
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

    protected function createComponentFormAddTravel() : Form
    {
        $form = new BaseForm();
        $form->getElementPrototype()->class('form-inline');
        $form->addHidden('command_id');
        $form->addSelect('type');
        $form->addDate('start_date', 'Datum cesty')
            ->setAttribute('class', 'form-control input-sm date')
            ->addRule(Form::FILLED, 'Musíte vyplnit datum cesty.');
        $form->addText('start_place', 'Z*')
            ->setAttribute('class', 'form-control input-sm')
            ->addRule(Form::FILLED, 'Musíte vyplnit místo počátku cesty.');
        $form->addText('end_place', 'Do*')
            ->setAttribute('class', 'form-control input-sm')
            ->addRule(Form::FILLED, 'Musíte vyplnit místo konce cesty.');
        $form->addText('distance', 'Vzdálenost*')
            ->setAttribute('class', 'form-control input-sm')
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
            $v->type,
            $v->start_date,
            $v->start_place,
            $v->end_place,
            $v->distance
        );
        $this->flashMessage('Cesta byla přidána.');
        $this->redirect('this');
    }

    public function actionEditTravel(int $commandId, int $travelId) : void
    {
        $travel = $this->travelService->getTravel($commandId, $travelId);

        if (! $this->isCommandEditable($commandId) || $travel === null) {
            $this->flashMessage('Záznam nelze upravovat', 'warning');
            $this->redirect('default');
        }
        $command = $this->travelService->getCommandDetail($commandId);

        $form = $this['formEditTravel'];

        $this->getTypeSelectBox()->setItems($command->getTransportTypePairs());

        $form->setDefaults(
            [
            'commandId' => $commandId,
            'id' => $travelId,
            'type' => $travel->getDetails()->getTransportType(),
            'date' => $travel->getDetails()->getDate(),
            'startPlace' => $travel->getDetails()->getStartPlace(),
            'endPlace' => $travel->getDetails()->getEndPlace(),
            'distanceOrPrice' => $travel->getDistance() ?? MoneyFactory::toFloat($travel->getPrice()),
            ]
        );

        $this->template->setParameters(['form' => $form]);
    }

    protected function createComponentFormEditTravel() : Form
    {
        $form = new BaseForm();

        $form->addHidden('commandId');
        $form->addHidden('id');
        $form->addSelect('type', 'Prostředek');
        $form->addDate('date', 'Datum cesty')
            ->setAttribute('class', 'form-control input-sm date')
            ->addRule(Form::FILLED, 'Musíte vyplnit datum cesty.');
        $form->addText('startPlace', 'Z*')
            ->setAttribute('class', 'form-control input-sm date')
            ->addRule(Form::FILLED, 'Musíte vyplnit místo počátku cesty.');
        $form->addText('endPlace', 'Do*')
            ->setAttribute('class', 'form-control input-sm date')
            ->addRule(Form::FILLED, 'Musíte vyplnit místo konce cesty.');
        $form->addText('distanceOrPrice', 'Vzdálenost*')
            ->setAttribute('class', 'form-control input-sm date')
            ->setRequired('Musíte vyplnit vzdálenost.')
            ->addRule(Form::FLOAT, 'Vzdálenost musí být číslo.')
            ->addRule(Form::MIN, 'Vzdálenost musí být větší než 0.', 0.01);
        $form->addSubmit('send', 'Upravit')
            ->setAttribute('class', 'btn btn-primary');

        $form->onSuccess[] = function (Form $form) : void {
            $this->formEditTravelSubmitted($form);
        };

        return $form;
    }

    protected function createComponentGrid() : CommandGrid
    {
        return $this->gridFactory->create($this->getUnitId(), $this->getUser()->getId());
    }

    private function formEditTravelSubmitted(Form $form) : void
    {
        $v = $form->getValues();

        $commandId = (int) $v->commandId;

        if (! $this->isCommandEditable($commandId)) {
            $this->flashMessage('Nelze upravovat cestovní příkaz.', 'danger');
            $this->redirect('default');
        }

        $this->travelService->updateTravel(
            $commandId,
            (int) $v->id,
            (float) $v->distanceOrPrice,
            $v->date,
            $v->type,
            $v->startPlace,
            $v->endPlace
        );

        $this->flashMessage('Cesta byla upravena.');
        $this->redirect('detail', [$v->commandId]);
    }

    private function getTypeSelectBox() : SelectBox
    {
        $selectBox = $this['formEditTravel']['type'];

        assert($selectBox instanceof SelectBox);

        return $selectBox;
    }
}
