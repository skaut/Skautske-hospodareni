<?php

namespace App\AccountancyModule\TravelModule;

use Nette\Application\UI\Form;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class DefaultPresenter extends BasePresenter {

    /**
     *
     * @var \Model\TravelService
     */
    protected $travelService;

    public function __construct(\Model\TravelService $ts) {
        parent::__construct();
        $this->travelService = $ts;
    }

    protected function isCommandAccessible($commandId) {
        return $this->travelService->isCommandAccessible($commandId, $this->unit);
    }

    protected function isContractAccessible($contractId) {
        return $this->travelService->isContractAccessible($contractId, $this->unit);
    }

    protected function isCommandEditable($id) {
        $this->template->command = $command = $this->travelService->getCommand($id);
        return ($this->isCommandAccessible($id) && $command->closed == NULL) ? true : false;
    }

    public function renderDefault() {
        $this->template->list = $this->travelService->getAllCommands($this->unit->ID);
    }

    public function actionDetail($id) {
        if ($id == NULL) {
            $this->redirect("default");
        }
        if (!$this->isCommandAccessible($id)) {
            $this->flashMessage("Neoprávněný přístup k záznamu!", "danger");
            $this->redirect("default");
        }
        $this['formAddTravel']['type']->setItems($this->travelService->getCommandTypes($id));
        $this['formAddTravel']->setDefaults(array("command_id" => $id));
    }

    public function renderDetail($id) {
        $this->template->command = $command = $this->travelService->getCommand($id);
        $this->template->contract = $contract = $this->travelService->getContract($command->contract_id);
        $this->template->isEditable = $this->isEditable = ($this->unit->ID == $contract->unit_id && $command->closed == NULL) ? true : false;
        $this->template->travels = $this->travelService->getTravels($command->id);
        $this->template->types = $this->travelService->getCommandTypes($command->id);
    }

    function actionEditCommand($commandId) {
        if (!$this->isCommandAccessible($commandId)) {
            $this->flashMessage("Nemáte oprávnění upravovat záznam!", "danger");
            $this->redirect("default");
        }
        if (!$this->isCommandEditable($commandId)) {
            $this->flashMessage("Záznam nelze upravovat", "warning");
            $this->redirect("default");
        }
        $this['formEditCommand']['type']->setItems($this->travelService->getTravelTypes(TRUE));
        $this['formEditCommand']->setDefaults(array("command_id" => $commandId));
    }

    function renderEditCommand($commandId) {
        $defaults = $this->travelService->getCommand($commandId);
        $defaults["type"] = array_keys($this->travelService->getCommandTypes($commandId));
        $defaults['id'] = $commandId;
        $form = $this['formEditCommand'];
        $form->setDefaults($defaults);
        $this->template->form = $form;
    }

    public function actionPrint($commandId) {
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
        //        echo $template;die();
        $this->travelService->makePdf($template, "cestovni-prikaz.pdf");
        $this->terminate();
    }

    public function handleCloseCommand($commandId) {
        if (!$this->isCommandAccessible($commandId)) {
            $this->flashMessage("Nemáte právo uzavřít cestovní příkaz.", "danger");
            $this->redirect("default");
        }
//        $who = $this->userService->getPersonalDetail()->Email;
        $this->travelService->closeCommand($commandId);
        $this->flashMessage("Cestovní příkaz byl uzavřen.");
        $this->redirect("this");
    }

    public function handleOpenCommand($commandId) {
        if (!$this->isCommandAccessible($commandId)) {
            $this->flashMessage("Nemáte právo otevřít cestovní příkaz.", "danger");
            $this->redirect("default");
        }
//        $who = $this->userService->getPersonalDetail()->Email;
        $this->travelService->openCommand($commandId);
        $this->flashMessage("Cestovní příkaz byl otevřen.");
        $this->redirect("this");
    }

    public function handleRemoveTravel($travelId) {
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

    public function handleRemoveCommand($commandId) {
        if (!$this->isCommandAccessible($commandId)) {
            $this->flashMessage("Nemáte právo upravovat záznam.", "danger");
            $this->redirect("default");
        }

        $this->travelService->deleteCommand($commandId);
        $this->flashMessage("Cestovní příkaz byl smazán.");
        $this->redirect("default");
    }

    protected function makeCommandForm($name) {
        $contracts = $this->travelService->getAllContractsPairs($this->unit->ID);
        $vehicles = $this->travelService->getVehiclesPairs($this->unit->ID);

        if (!empty($contracts["past"])) {
            $contracts = array("platné" => $contracts["valid"], "ukončené" => $contracts["past"]);
        } else {
            $contracts = $contracts["valid"];
        }

        $vehicleTypes = $this->travelService->getTravelTypes();
        $vehiclesWithFuel = array_map(function($v) {
            return $v->type;
        }, array_filter($vehicleTypes, function ($v) {
                    return $v->hasFuel;
                }));
        $vehicleTypes = array_map(function($v) {
            return $v->label;
        }, $vehicleTypes);

        $form = $this->prepareForm($this, $name);
        $form->addText("purpose", "Účel cesty*")
                ->setMaxLength(64)
                ->setAttribute("class", "form-control")
                ->addRule(Form::FILLED, "Musíte vyplnit účel cesty.");
        $form->addSelect("contract_id", "Smlouva", $contracts)
                ->setPrompt("Vyberte smlouvu")
                ->setAttribute("class", "form-control")
                ->addRule(Form::FILLED, "Musíte vybrat smlouvu");
        $form->addCheckboxList("type", "Prostředek", $vehicleTypes)
                ->addRule(Form::FILLED, "Vyberte alespoň jeden dopravní prostředek.");
        $form->addText("place", "Místo")
                ->setMaxLength(64)
                ->setAttribute("class", "form-control");

        $form->addText("passengers", "Spolucestující")
                ->setMaxLength(64)
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
        $form->addText("note", "Poznámka")
                ->setMaxLength(64)
                ->setAttribute("class", "form-control");
        $form->onSuccess[] = array($this, $name . 'Submitted');
        return $form;
    }

    function createComponentFormCreateCommand($name) {
        $form = $this->makeCommandForm($name);
        $form->addSubmit('send', 'Založit')
                ->setAttribute("class", "btn btn-primary");
        return $form;
    }

    function formCreateCommandSubmitted(Form $form) {
        $v = $form->getValues();
        if (isset($v->contract_id) && !$this->isContractAccessible($v->contract_id)) {
            $this->flashMessage("Nemáte právo založit cestovní příkaz.", "danger");
            $this->redirect("default");
        }
//        $v['state'] = "open";
        $this->travelService->addCommand($v);
        $this->flashMessage("Cestovní příkaz byl založen.");
        $this->redirect("this");
    }

    function createComponentFormEditCommand($name) {
        $form = $this->makeCommandForm($name);
        $form->addSubmit('send', 'Upravit')
                ->setAttribute("class", "btn btn-primary");
        $form->addHidden("id");
        return $form;
    }

    function formEditCommandSubmitted(Form $form) {
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
        $this->redirect("detail", array("id" => $id));
    }

    public function createComponentFormAddTravel($name) {
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
        $form->onSuccess[] = array($this, $name . 'Submitted');
        return $form;
    }

    function formAddTravelSubmitted(Form $form) {
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

    function actionEditTravel($travelId) {
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

    public function createComponentFormEditTravel($name) {
        $form = $this->prepareForm($this, $name);
//        $form->getElementPrototype()->class("form-inline");
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
        $form->onSuccess[] = array($this, $name . 'Submitted');
        return $form;
    }

    function formEditTravelSubmitted(Form $form) {
        $v = $form->getValues();
        $tid = $v->id;
        $oldTravel = $this->travelService->getTravel($tid);
        if (!$this->isCommandEditable($oldTravel['command_id'])) {
            $this->flashMessage("Nelze upravovat cestovní příkaz.", "danger");
            $this->redirect("default");
        }
        $data = array();
        $keys = array("start_date", "start_place", "end_place", "distance", "type");
        foreach ($keys as $k) {
            $data[$k] = $k == "distance" ? str_replace(",", ".", $v[$k]) : $v[$k];
        }
        $this->travelService->updateTravel($data, $tid);
        $this->flashMessage("Cesta byla upravena.");
        $this->redirect("detail", array("id" => $oldTravel['command_id']));
    }

}
