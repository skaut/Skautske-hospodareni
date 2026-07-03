<?php

declare(strict_types=1);

namespace App\Presentation\Travel\Command;

use App\Components\Factories\Travel\ICommandFormFactory;
use App\Components\Factories\Travel\IEditTravelDialogFactory;
use App\Components\Travel\CommandForm;
use App\Components\Travel\EditTravelDialog;
use App\Model\Services\PdfRenderer;
use App\Model\Travel\Commands\Command\AddReturnTravel;
use App\Model\Travel\Commands\Command\DuplicateTravel;
use App\Model\Travel\Travel\TransportType;
use App\Model\Travel\TravelService;
use App\Model\User\UserService;
use Assert\Assertion;
use Cake\Chronos\ChronosDate;
use Component\Forms\BaseForm;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Forms\Controls\SelectBox;
use Nette\Http\IResponse;
use Nette\Security\SimpleIdentity;

use function array_key_exists;
use function array_map;
use function array_slice;
use function assert;
use function round;
use function sprintf;
use function str_replace;

final class CommandPresenter extends \App\BasePresenter
{
    private ?int $commandId = null;

    public function __construct(
        private readonly ICommandFormFactory $commandFormFactory,
        private readonly IEditTravelDialogFactory $editTravelDialogFactory,
        private readonly TravelService $travelService,
        private readonly PdfRenderer $pdf,
    ) {
        parent::__construct();
    }

    public function actionEdit(int $id): void
    {
        $command = $this->travelService->getCommandDetail($id);
        if ($command === null || $command->getClosedAt() !== null) {
            throw new BadRequestException('Cestovní příkaz #'.$id.' neexistuje');
        }

        if ($command->getUnitId() !== $this->getUnitId() && $this->getUser()->getId() !== $command->getOwnerId()) {
            throw new BadRequestException('Nemáte oprávnění upravovat zvolený doklad', IResponse::S403_Forbidden);
        }

        $this->commandId = $id;
    }

    public function actionDetail(int $id): void
    {
        $this->commandId = $id;

        if (! $this->isCommandAccessible($id)) {
            $this->flashMessage('Neoprávněný přístup k záznamu!', 'danger');
            $this->redirect(':Travel:Default:default');
        }

        $command = $this->travelService->getCommandDetail($id);
        assert($command !== null);

        $form = $this['formAddTravel'];
        assert($form instanceof BaseForm);

        $this->getTypeSelectBox($form)->setItems($command->getTransportTypePairs());
        $form->setDefaults(['command_id' => $id]);
    }

    public function renderDetail(int $id): void
    {
        $command = $this->travelService->getCommandDetail($id);
        assert($command !== null);

        $vehicle = $command->getVehicleId() !== null
            ? $this->travelService->getVehicleDTO($command->getVehicleId())
            : null;

        $this->template->setParameters([
            'command' => $command,
            'vehicle' => $vehicle,
            'types' => $command->getTransportTypePairs(),
            'isEditable' => $this->isCommandEditable($command->getId()),
            'travels' => $this->travelService->getTravels($command->getId()),
        ]);
    }

    public function actionPrint(int $id): void
    {
        if (! $this->isCommandAccessible($id)) {
            $this->flashMessage('Neoprávněný přístup k záznamu!', 'danger');
            $this->redirect(':Travel:Default:default');
        }

        $command = $this->travelService->getCommandDetail($id);
        assert($command !== null);

        $travels = $this->travelService->getTravels($id);
        $vehicleId = $command->getVehicleId();

        $template = $this->getTemplateFactory()->createTemplate();
        assert($template instanceof Template);

        $template->getLatte()->addFilterLoader('\\App\\Helpers\AccountancyHelpers::loader');

        $this->pdf->render(
            $template->renderToString(
                __DIR__.'/ex.command.latte',
                [
                    'command' => $command,
                    'travels' => $travels,
                    'types' => array_map(fn (TransportType $t) => $t->getLabel(), $command->getTransportTypes()),
                    'vehicle' => $vehicleId !== null ? $this->travelService->findVehicle($vehicleId) : null,
                    'start' => $travels[0] ?? null,
                    'end' => array_slice($travels, -1)[0] ?? null,
                ],
            ),
            'cestovni-prikaz.pdf',
        );
        $this->terminate();
    }

    public function handleCloseCommand(int $commandId): void
    {
        if (! $this->isCommandAccessible($commandId)) {
            $this->flashMessage('Nemáte právo uzavřít cestovní příkaz.', 'danger');
            $this->redirect(':Travel:Default:default');
        }

        $this->travelService->closeCommand($commandId);
        $this->flashMessage('Cestovní příkaz byl uzavřen.');
        $this->redirect('this');
    }

    public function handleDuplicateTravel(int $commandId, int $travelId): void
    {
        if (! $this->isCommandEditable($commandId)) {
            $this->flashMessage('Nemáte oprávnění duplikovat cestu.', 'danger');
            $this->redirect(':Travel:Default:default');
        }

        $this->commandBus->handle(new DuplicateTravel($commandId, $travelId));
        $this->flashMessage('Cesta byla duplikována.', 'success');
        $this->redirect('this');
    }

    public function handleAddReturnTravel(int $commandId, int $travelId): void
    {
        if (! $this->isCommandEditable($commandId)) {
            $this->flashMessage('Nemáte oprávnění přidat zpáteční cestu.', 'danger');
            $this->redirect(':Travel:Default:default');
        }

        $this->commandBus->handle(new AddReturnTravel($commandId, $travelId));
        $this->flashMessage('Zpáteční cesta byla přidána.', 'success');
        $this->redirect('this');
    }

    public function handleOpenCommand(int $commandId): void
    {
        if (! $this->isCommandAccessible($commandId)) {
            $this->flashMessage('Nemáte právo otevřít cestovní příkaz.', 'danger');
            $this->redirect(':Travel:Default:default');
        }

        $this->travelService->openCommand($commandId);
        $this->flashMessage('Cestovní příkaz byl otevřen.');
        $this->redirect('this');
    }

    public function handleRemoveTravel(int $commandId, int $travelId): void
    {
        if (! $this->isCommandEditable($commandId)) {
            $this->flashMessage('Nemáte oprávnění smazat cestu.', 'danger');
            $this->redirect('this');
        }

        $this->travelService->removeTravel($commandId, $travelId);
        $this->flashMessage('Cesta byla smazána.');
        $this->redirect('this');
    }

    public function handleRemoveCommand(int $commandId): void
    {
        if (! $this->isCommandAccessible($commandId)) {
            $this->flashMessage('Nemáte právo upravovat záznam.', 'danger');
            $this->redirect(':Travel:Default:default');
        }

        $this->travelService->deleteCommand($commandId);
        $this->flashMessage('Cestovní příkaz byl smazán.');
        $this->redirect(':Travel:Default:default');
    }

    public function handleEditTravel(int $travelId): void
    {
        $this['editTravelDialog']->open($travelId);
    }

    protected function createComponentForm(): CommandForm
    {
        $form = $this->commandFormFactory->create($this->getUnitId(), $this->commandId);
        $form->onSuccess[] = function (): void {
            if ($this->commandId !== null) {
                $this->redirect('detail', ['id' => $this->commandId]);
            }

            $this->redirect(':Travel:Default:default');
        };

        return $form;
    }

    protected function createComponentFormAddTravel(): BaseForm
    {
        $form = new BaseForm();

        $form->addHidden('command_id');
        $form->addSelect('type');
        $form->addDate('start_date', 'Datum cesty')
            ->setHtmlAttribute('class', 'date')
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
            ->setHtmlAttribute('class', 'btn btn-primary');

        $form->onSuccess[] = function (Form $form): void {
            $this->formAddTravelSubmitted($form);
        };

        return $form;
    }

    protected function createComponentEditTravelDialog(): EditTravelDialog
    {
        $commandId = $this->commandId;
        Assertion::notNull($commandId);

        if (! $this->isCommandEditable($commandId)) {
            throw new BadRequestException(sprintf('User cannot edit command %d', $commandId), IResponse::S403_Forbidden);
        }

        return $this->editTravelDialogFactory->create($commandId);
    }

    private function formAddTravelSubmitted(Form $form): void
    {
        $values = $form->getValues();
        $commandId = (int) $values['command_id'];

        if (! $this->isCommandEditable($commandId)) {
            $this->flashMessage('Nelze upravovat cestovní příkaz.', 'danger');
            $this->redirect(':Travel:Default:default');
        }

        $values['distance'] = round((float) str_replace(',', '.', (string) $values['distance']), 2);

        $this->travelService->addTravel(
            $commandId,
            TransportType::get($values->type),
            new ChronosDate($values['start_date']),
            $values['start_place'],
            $values['end_place'],
            $values['distance'],
        );
        $this->flashMessage('Cesta byla přidána.');
        $this->redirect('this');
    }

    private function getTypeSelectBox(BaseForm $form): SelectBox
    {
        $selectBox = $form['type'];
        assert($selectBox instanceof SelectBox);

        return $selectBox;
    }

    private function isCommandAccessible(int $commandId): bool
    {
        $command = $this->travelService->getCommandDetail($commandId);
        if ($command === null) {
            return false;
        }

        $identity = $this->getUser()->getIdentity();
        assert($identity instanceof SimpleIdentity);

        return $command->getOwnerId() === $this->getUser()->getId()
            || array_key_exists($command->getUnitId(), $identity->access[UserService::ACCESS_READ]);
    }

    private function isCommandEditable(int $commandId): bool
    {
        $command = $this->travelService->getCommandDetail($commandId);
        assert($command !== null);

        $identity = $this->getUser()->getIdentity();
        assert($identity instanceof SimpleIdentity);

        $unitOrOwner = $command->getOwnerId() === $this->getUser()->getId()
            || array_key_exists($command->getUnitId(), $identity->access[UserService::ACCESS_EDIT]);

        return $this->isCommandAccessible($commandId)
            && $command->getClosedAt() === null
            && $unitOrOwner;
    }
}
