<?php

namespace App\AccountancyModule\TravelModule;

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
        $this->template->list = $this->travelService->getAllCommands($this->unit->ID);
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

    public function renderDetail($id) : void
    {
        $this->template->command = $command = $this->travelService->getCommand($id);
        $this->template->contract = $contract = $this->travelService->getContract($command->contract_id);
        $this->template->isEditable = $this->isEditable = ($this->unit->ID == $command->unit_id && $command->closed == NULL) ? TRUE : FALSE;
        $this->template->travels = $this->travelService->getTravels($command->id);
        $this->template->types = $this->travelService->getCommandTypes($command->id);
    }

    public function actionEditCommand($commandId) : void
    {
        if (!$this->isCommandAccessible($commandId)) {
            $this->flashMessage("Nemáte oprávnění upravovat záznam!", "danger");
            $this->redirect("default");
        }
        if (!$this->isCommandEditable($commandId)) {
            $this->flashMessage("Záznam nelze upravovat", "warning");
            $this->redirect("default");
        }
        $this['formEditCommand']['type']->setItems($this->travelService->getTravelTypes(TRUE));
        $this['formEditCommand']->setDefaults(["command_id" => $commandId]);
    }

    public function renderEditCommand($commandId) : void
    {
        $defaults = $this->travelService->getCommand($commandId);
        $defaults["type"] = array_keys($this->travelService->getCommandTypes($commandId));
        $defaults['id'] = $commandId;
        $form = $this['formEditCommand'];

        $index = 'vehicle_id';
        // If command uses archived vehicle, add it to select
        $items = $form[$index]->items;
        if (!in_array($defaults[$index], $items)) {
            try {
                $vehicle = $this->travelService->getVehicle((int)$defaults[$index]);
                $form[$index]->setItems([$vehicle->getId() => $vehicle->getLabel()] + $items);
            } catch (\Model\Travel\VehicleNotFoundException $exc) {

            }
        }

        $form->setDefaults($defaults);
        $this->template->form = $form;
    }

    public function actionPrint($commandId) : void
    {
        if (!$this->isCommandAccessible($commandId)) {
            $this->flashMessage("Neoprávněný přístup k záznamu!", "danger");
            $this->redirect("default");
        }
        $template = $this->template;
        $template->getLatte()->addFilter(NULL, "\App\AccountancyModule\AccountancyHelpers::loader");
        $template->setFile(dirname(__FILE__) . '/../templates/Default/ex.command.latte');
        $template->command = $command = $this->travelService->getCommand($commandId);
        $template->contract = $this->travelService->getContract($command->contract_id);
        $template->travels = $travels = $this->travelService->getTravels($command->id);
        $template->types = $this->travelService->getCommandTypes($command->id);
        if (!empty($travels)) {
            $template->end = end($travels);
            $template->start = reset($travels);
        }

        $this->pdf->render($template, 'cestovni-prikaz.pdf');
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

    public function handleRemoveCommand($commandId) : void
    {
        if (!$this->isCommandAccessible($commandId)) {
            $this->flashMessage("Nemáte právo upravovat záznam.", "danger");
            $this->redirect("default");
        }

        $this->travelService->deleteCommand($commandId);
        $this->flashMessage("Cestovní příkaz byl smazán.");
        $this->redirect("default");
    }

    private function makeCommandForm($name) : Form
    {
        $contracts = $this->travelService->getAllContractsPairs($this->unit->ID);
        $vehicles = $this->travelService->getVehiclesPairs($this->unit->ID);

        if (!empty($contracts["past"])) {
            $contracts = ["platné" => $contracts["valid"], "ukončené" => $contracts["past"]];
        } else {
            $contracts = $contracts["valid"];
        }

        $vehicleTypes = $this->travelService->getTravelTypes();
        $vehiclesWithFuel = array_map(function ($v) {
            return $v->type;
        }, array_filter($vehicleTypes, function ($v) {
            return $v->hasFuel;
        }));
        $vehicleTypes = array_map(function ($v) {
            return $v->label;
        }, $vehicleTypes);

        $form = $this->prepareForm($this, $name);
        $form->addText("unit", "Jednotka")->setDefaultValue($this->unit->SortName)->setOmitted()->getControlPrototype()->DISABLED("DISABLED");
        $form->addText("purpose", "Účel cesty*")
            ->setMaxLength(64)
            ->setAttribute("class", "form-control")
            ->addRule(Form::FILLED, "Musíte vyplnit účel cesty.");
        $form->addCheckboxList("type", "Prostředek*", $vehicleTypes)
            ->addRule(Form::FILLED, "Vyberte alespoň jeden dopravní prostředek.");
        $form->addSelect("contract_id", "Smlouva/Řidič*", $contracts)
            ->setPrompt("Vyberte smlouvu")
            ->setAttribute("class", "form-control");
        $form->addSelect("vehicle_id", "Vozidlo*", $vehicles)
            ->setPrompt("Vyberte vozidlo")
            ->setAttribute("class", "form-control")
            ->addConditionOn($form['type'], Form::IS_IN, $vehiclesWithFuel)->addRule(Form::FILLED, "Musíte vyplnit typ vozidla.");
        $form->addText("fuel_price", "Cena paliva za 1l*")
            ->setAttribute("class", "form-control")
            ->addConditionOn($form['type'], Form::IS_IN, $vehiclesWithFuel)->addRule(Form::FILLED, "Musíte vyplnit cenu paliva.")->addRule(Form::FLOAT, "Musíte zadat desetinné číslo.");
        $form->addText("amortization", "Opotřebení*")
            ->setAttribute("class", "form-control")
            ->addConditionOn($form['type'], Form::IS_IN, $vehiclesWithFuel)->addRule(Form::FILLED, "Musíte vyplnit opotřebení.")->addRule(Form::FLOAT, "Musíte zadat desetinné číslo.");

        $form->addText("place", "Místo")
            ->setMaxLength(64)
            ->setAttribute("class", "form-control");
        $form->addText("passengers", "Spolucestující")
            ->setMaxLength(64)
            ->setAttribute("class", "form-control");
        $form->addText("note", "Poznámka")
            ->setMaxLength(64)
            ->setAttribute("class", "form-control");
        $form->onSuccess[] = [$this, $name . 'Submitted'];
        return $form;
    }

    protected function createComponentFormCreateCommand($name) : Form
    {
        $form = $this->makeCommandForm($name);
        $form->addSubmit('send', 'Založit')
            ->setAttribute("class", "btn btn-primary");
        return $form;
    }

    /**
     * @param Form $form
     * @TODO refactor to private
     */
    public function formCreateCommandSubmitted(Form $form) : void
    {
        $v = $form->getValues();
        if (isset($v->contract_id) && !$this->isContractAccessible($v->contract_id)) {
            $this->flashMessage("Nemáte právo založit cestovní příkaz.", "danger");
            $this->redirect("default");
        }
        $v->unit_id = $this->unit->ID;

        $this->travelService->addCommand($v);
        $this->flashMessage("Cestovní příkaz byl založen.");
        $this->redirect("this");
    }

    protected function createComponentFormEditCommand($name) : Form
    {
        $form = $this->makeCommandForm($name);
        $form->addSubmit('send', 'Upravit')
            ->setAttribute("class", "btn btn-primary");
        $form->addHidden("id");
        return $form;
    }

    /**
     * @param Form $form
     * @TODO refactor to private
     */
    public function formEditCommandSubmitted(Form $form) : void
    {
        $v = $form->getValues();
        $id = $v['id'];
        unset($v['id']);

        if (!$this->isCommandEditable($id)) {
            $this->flashMessage("Nemáte právo upravovat cestovní příkaz.", "danger");
            $this->redirect("default");
        }
        if ($this->travelService->updateCommand($v, $this->unit, $id)) {
            $this->flashMessage("Cestovní příkaz byl upraven.");
        } else {
            $this->flashMessage("Cestovní příkaz se nepodařilo upravit.", "danger");
        }
        $this->redirect("detail", ["id" => $id]);
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

        $this->travelService->addTravel($v);
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
