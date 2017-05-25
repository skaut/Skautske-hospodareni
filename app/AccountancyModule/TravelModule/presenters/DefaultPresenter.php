<?php

namespace App\AccountancyModule\TravelModule;

use Model\DTO\Travel\Command;
use Model\Services\PdfRenderer;
use Model\TravelService;
use Nette\Application\UI\Form;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class DefaultPresenter extends BasePresenter
{

    /** @var TravelService */
    private $travelService;

    /** @var PdfRenderer */
    private $pdf;

    public function __construct(TravelService $travelService, PdfRenderer $pdf)
    {
        parent::__construct();
        $this->travelService = $travelService;
        $this->pdf = $pdf;
    }

    protected function isCommandAccessible($commandId) : bool
    {
        return $this->travelService->isCommandAccessible($commandId, $this->unit);
    }

    protected function isContractAccessible($contractId) : bool
    {
        return $this->travelService->isContractAccessible($contractId, $this->unit);
    }

    protected function isCommandEditable($id) : bool
    {
        $this->template->command = $command = $this->travelService->getCommand($id);
        return ($this->isCommandAccessible($id) && $command->closed == NULL) ? TRUE : FALSE;
    }

    public function renderDefault() : void
    {
        $commands = $this->travelService->getAllCommands($this->getUnitId());

        $vehicleIds = array_map(function (Command $command) {
            return $command->getVehicleId();
        }, $commands);

        $commandIds = array_map(function(Command $command) {
            return $command->getId();
        }, $commands);

        $this->template->list = $commands;
        $this->template->vehicles = $this->travelService->findVehiclesByIds(array_unique(array_filter($vehicleIds)));
        $this->template->types = $this->travelService->getTypes($commandIds);
    }

    public function actionDetail($id) : void
    {
        if ($id == NULL) {
            $this->redirect("default");
        }
        if (!$this->isCommandAccessible($id)) {
            $this->flashMessage("Neoprávněný přístup k záznamu!", "danger");
            $this->redirect("default");
        }
        $this['formAddTravel']['type']->setItems($this->travelService->getCommandTypes($id));
        $this['formAddTravel']->setDefaults(["command_id" => $id]);
    }

    public function renderDetail(int $id): void
    {
        $this->template->command = $command = $this->travelService->getCommandDetail($id);
        $this->template->vehicle = $command->getVehicleId() !== NULL
                                 ? $this->travelService->getVehicle($command->getVehicleId())
                                 : NULL;
        $this->template->contract = $contract = $this->travelService->getContract($command->getPassenger()->getContractId());
        $this->template->isEditable = $this->isEditable = $this->unit->ID === $command->getUnitId() && $command->getClosedAt() === NULL;
        $this->template->travels = $this->travelService->getTravels($command->getId());
        $this->template->types = $this->travelService->getCommandTypes($command->getId());
    }

    public function actionPrint(int $commandId) : void
    {
        if (!$this->isCommandAccessible($commandId)) {
            $this->flashMessage("Neoprávněný přístup k záznamu!", "danger");
            $this->redirect("default");
        }

        $command = $this->travelService->getCommandDetail($commandId);
        $travels = $this->travelService->getTravels($commandId);
        $vehicleId = $command->getVehicleId();

        /* @var $template \Nette\Bridges\ApplicationLatte\Template */
        $template = $this->getTemplateFactory()->createTemplate();
        $template->setParameters([
            "command" => $command,
            "travels" => $travels,
            "types" => $this->travelService->getCommandTypes($commandId),
            "vehicle" => $vehicleId !== NULL ? $this->travelService->findVehicle($vehicleId) : NULL,
        ]);

        if (count($travels) !== 0) {
            $template->setParameters([
                "start" => $travels[0],
                "end" => array_slice($travels, -1)[0],
            ]);
        }

        $template->getLatte()->addFilter(NULL, "\\App\\AccountancyModule\\AccountancyHelpers::loader");
        $template->setFile(__DIR__ . '/../templates/Default/ex.command.latte');

        $this->pdf->render((string)$template, 'cestovni-prikaz.pdf');
        $this->terminate();
    }

    public function handleCloseCommand($commandId) : void
    {
        if (!$this->isCommandAccessible($commandId)) {
            $this->flashMessage("Nemáte právo uzavřít cestovní příkaz.", "danger");
            $this->redirect("default");
        }

        $this->travelService->closeCommand($commandId);
        $this->flashMessage("Cestovní příkaz byl uzavřen.");
        $this->redirect("this");
    }

    public function handleOpenCommand($commandId) : void
    {
        if (!$this->isCommandAccessible($commandId)) {
            $this->flashMessage("Nemáte právo otevřít cestovní příkaz.", "danger");
            $this->redirect("default");
        }

        $this->travelService->openCommand($commandId);
        $this->flashMessage("Cestovní příkaz byl otevřen.");
        $this->redirect("this");
    }

    public function handleRemoveTravel($travelId) : void
    {
        $travel = $this->travelService->getTravel($travelId);
        $command = $this->travelService->getCommand($travel->command_id);
        if (!$this->isCommandEditable($command->id)) {
            $this->flashMessage("Nemáte právo upravovat záznam.", "danger");
            $this->redirect("default");
        }
        $contract = $this->travelService->getContract($command->contract_id);
        if ($this->unit->ID == $contract->unit_id && $command->closed == NULL) {
            $this->travelService->deleteTravel($travelId);
            $this->flashMessage("Cesta z \"$travel->start_place\" do \"$travel->end_place\" byla smazána.");
        } else {
            $this->flashMessage("Nemáte oprávnění smazat cestu.", "danger");
        }
        $this->redirect("this");
    }

    public function handleRemoveCommand(int $commandId) : void
    {
        if (!$this->isCommandAccessible($commandId)) {
            $this->flashMessage("Nemáte právo upravovat záznam.", "danger");
            $this->redirect("default");
        }

        $this->travelService->deleteCommand($commandId);
        $this->flashMessage("Cestovní příkaz byl smazán.");
        $this->redirect("default");
    }

    protected function createComponentFormAddTravel($name) : Form
    {
        $form = $this->prepareForm($this, $name);
        $form->getElementPrototype()->class("form-inline");
        $form->addHidden("command_id");
        $form->addSelect("type");
        $form->addDatePicker("start_date", "Datum cesty")
            ->setAttribute("class", "form-control input-sm date")
            ->addRule(Form::FILLED, "Musíte vyplnit datum cesty.");
        $form->addText("start_place", "Z*")
            ->setAttribute("class", "form-control input-sm")
            ->addRule(Form::FILLED, "Musíte vyplnit místo počátku cesty.");
        $form->addText("end_place", "Do*")
            ->setAttribute("class", "form-control input-sm")
            ->addRule(Form::FILLED, "Musíte vyplnit místo konce cesty.");
        $form->addText("distance", "Vzdálenost*")
            ->setAttribute("class", "form-control input-sm")
            ->addRule(Form::FILLED, "Musíte vyplnit vzdálenost.")
            ->addRule(Form::FLOAT, "Vzdálenost musí být číslo!");
        $form->addSubmit('send', 'Přidat')
            ->setAttribute("class", "btn btn-primary");

        $form->onSuccess[] = function(Form $form) : void {
            $this->formAddTravelSubmitted($form);
        };
        return $form;
    }

    private function formAddTravelSubmitted(Form $form) : void
    {
        $v = $form->getValues();
        if (!$this->isCommandEditable($v['command_id'])) {
            $this->flashMessage("Nelze upravovat cestovní příkaz.", "danger");
            $this->redirect("default");
        }
        $v['distance'] = round(str_replace(",", ".", $v['distance']), 2);

        $this->travelService->addTravel(
            (int)$v->command_id,
            $v->type,
            \DateTimeImmutable::createFromMutable($v->start_date),
            $v->start_place,
            $v->end_place,
            $v->distance);
        $this->flashMessage("Cesta byla přidána.");
        $this->redirect("this");
    }

    /*
     * EDIT TRAVEL
     */

    public function actionEditTravel($travelId) : void
    {
        $travel = $this->travelService->getTravel($travelId);
        if (!$this->isCommandAccessible($travel->command_id)) {
            $this->flashMessage("Nemáte oprávnění upravovat záznam!", "danger");
            $this->redirect("default");
        }
        if (!$this->isCommandEditable($travel->command_id)) {
            $this->flashMessage("Záznam nelze upravovat", "warning");
            $this->redirect("default");
        }
        $form = $this['formEditTravel'];
        $form['type']->setItems($this->travelService->getCommandTypes($travel->command_id));
        $form->setDefaults($travel);
        $this->template->form = $form;
    }

    protected function createComponentFormEditTravel($name) : Form
    {
        $form = $this->prepareForm($this, $name);

        $form->addHidden("command_id");
        $form->addHidden("id");
        $form->addSelect("type", "Prostředek");
        $form->addDatePicker("start_date", "Datum cesty")
            ->setAttribute("class", "form-control input-sm date")
            ->addRule(Form::FILLED, "Musíte vyplnit datum cesty.");
        $form->addText("start_place", "Z*")
            ->setAttribute("class", "form-control input-sm date")
            ->addRule(Form::FILLED, "Musíte vyplnit místo počátku cesty.");
        $form->addText("end_place", "Do*")
            ->setAttribute("class", "form-control input-sm date")
            ->addRule(Form::FILLED, "Musíte vyplnit místo konce cesty.");
        $form->addText("distance", "Vzdálenost*")
            ->setAttribute("class", "form-control input-sm date")
            ->addRule(Form::FILLED, "Musíte vyplnit vzdálenost.");
        $form->addSubmit('send', 'Upravit')
            ->setAttribute("class", "btn btn-primary");

        $form->onSuccess[] = function(Form $form) : void {
            $this->formEditTravelSubmitted($form);
        };

        return $form;
    }

    private function formEditTravelSubmitted(Form $form) : void
    {
        $v = $form->getValues();
        $tid = $v->id;
        $oldTravel = $this->travelService->getTravel($tid);
        if (!$this->isCommandEditable($oldTravel['command_id'])) {
            $this->flashMessage("Nelze upravovat cestovní příkaz.", "danger");
            $this->redirect("default");
        }
        $data = [];
        $keys = ["start_date", "start_place", "end_place", "distance", "type"];
        foreach ($keys as $k) {
            $data[$k] = $k == "distance" ? str_replace(",", ".", $v[$k]) : $v[$k];
        }
        $this->travelService->updateTravel($data, $tid);
        $this->flashMessage("Cesta byla upravena.");
        $this->redirect("detail", ["id" => $oldTravel['command_id']]);
    }

}
