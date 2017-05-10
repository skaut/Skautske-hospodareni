<?php

namespace App\AccountancyModule\TravelModule\Components;

use App\AccountancyModule\Factories\FormFactory;
use App\Forms\BaseForm;
use Model\Travel\Driver;
use Model\TravelService;
use Nette\Application\UI\Control;
use Nette\InvalidStateException;
use Nette\Utils\ArrayHash;

class CommandForm extends Control
{

    /** @var int */
    private $unitId;

    /** @var int|NULL */
    private $commandId;

    /** @var TravelService */
    private $model;

    /** @var FormFactory */
    private $formFactory;

    /** @var callable[] */
    public $onSuccess = [];

    public function __construct(int $unitId, ?int $commandId, TravelService $model, FormFactory $formFactory)
    {
        parent::__construct();
        $this->unitId = $unitId;
        $this->commandId = $commandId;
        $this->model = $model;
        $this->formFactory = $formFactory;
    }

    public function render(): void
    {
        $this["form"]->render();
    }

    protected function createComponentForm(): BaseForm
    {
        $contracts = $this->model->getAllContractsPairs($this->unitId);
        $vehicles = $this->model->getVehiclesPairs($this->unitId);

        if (!empty($contracts["past"])) {
            $contracts = ["platné" => $contracts["valid"], "ukončené" => $contracts["past"]];
        } else {
            $contracts = $contracts["valid"];
        }

        $transportTypes = $this->model->getTravelTypes();
        $vehiclesWithFuel = array_map(function ($v) {
            return $v->type;
        }, array_filter($transportTypes, function ($v) {
            return $v->hasFuel;
        }));
        $transportTypes = array_map(function ($v) {
            return $v->label;
        }, $transportTypes);

        $form = $this->formFactory->create();
        $form->addText("purpose", "Účel cesty*")
            ->setMaxLength(64)
            ->setAttribute("class", "form-control")
            ->addRule($form::FILLED, "Musíte vyplnit účel cesty.");
        $form->addCheckboxList("type", "Prostředek*", $transportTypes)
            ->setRequired("Vyberte alespoň jeden dopravní prostředek.");

        $form->addSelect("contract_id", "Smlouva/Řidič", $contracts)
            ->setPrompt("Vyberte smlouvu")
            ->setAttribute("class", "form-control")
            ->setOption("id", "contractId")
            ->addCondition($form::BLANK)
            ->addConditionOn($form["type"], $form::IS_NOT_IN, $vehiclesWithFuel)
            ->toggle("driverName")
            ->toggle("driverContact")
            ->toggle("driverAddress")
            ->endCondition()
            ->endCondition()
            ->addConditionOn($form["type"], $form::IS_NOT_IN, $vehiclesWithFuel)
            ->toggle("contractId");

        $form->addText("driverName", "Jméno řidiče")
            ->setOption("id", "driverName");
        $form->addText("driverContact", "Kontakt na řidiče")
            ->setOption("id", "driverContact");
        $form->addText("driverAddress", "Adresa řidiče")
            ->setOption("id", "driverAddress");

        $form->addSelect("vehicle_id", "Vozidlo*", $vehicles)
            ->setOption("id", "vehicle_id")
            ->setPrompt("Vyberte vozidlo")
            ->setAttribute("class", "form-control")
            ->addConditionOn($form['type'], $form::IS_IN, $vehiclesWithFuel)
            ->setRequired("Musíte vyplnit typ vozidla.")
            ->toggle("vehicle_id", FALSE);
        $form->addText("fuel_price", "Cena paliva za 1l*")
            ->setOption("id", "fuel_price")
            ->setAttribute("class", "form-control")
            ->addConditionOn($form['type'], $form::IS_IN, $vehiclesWithFuel)
            ->setRequired("Musíte vyplnit cenu paliva.")
            ->addRule($form::FLOAT, "Musíte zadat desetinné číslo.")
            ->toggle("fuel_price", FALSE);
        $form->addText("amortization", "Opotřebení*")
            ->setOption("id", "amortization")
            ->setAttribute("class", "form-control")
            ->addConditionOn($form['type'], $form::IS_IN, $vehiclesWithFuel)
            ->setRequired("Musíte vyplnit opotřebení.")
            ->addRule($form::FLOAT, "Musíte zadat desetinné číslo.")
            ->toggle("amortization", FALSE);

        $form->addText("place", "Místo")
            ->setMaxLength(64)
            ->setAttribute("class", "form-control");
        $form->addText("passengers", "Spolucestující")
            ->setMaxLength(64)
            ->setAttribute("class", "form-control");
        $form->addText("note", "Poznámka")
            ->setMaxLength(64)
            ->setAttribute("class", "form-control");

        $form->addSubmit("send", $this->commandId !== NULL ? "Upravit" : "Založit");

        $form->onSuccess[] = function (BaseForm $form) {
            if ($this->commandId === NULL) {
                $this->createCommand($form->getValues());
            } else {
                $this->updateCommand($form->getValues());
            }
            $this->onSuccess();
        };

        if($this->commandId !== NULL) {
            $this->loadDefaultValues($form);
        }

        return $form;
    }

    private function loadDefaultValues(BaseForm $form): void
    {
        $command = $this->model->getCommandDetail($this->commandId);

        if ($command === NULL) {
            throw new InvalidStateException("Travel command #{$this->commandId} not found");
        }

        $form->setDefaults([
            "contract_id" => $command->getDriver()->getContractId(),
            "purpose" => $command->getPurpose(),
            "place" => $command->getPlace(),
            "passengers" => $command->getPassengers(),
            "fuel_price" => $command->getFuelPrice(),
            "amortization" => $command->getAmortizationPerKm(),
            "note" => $command->getNote(),
            "type" => array_keys($this->model->getCommandTypes($this->commandId)),
        ]);

        $vehicleId = $command->getVehicleId();

        if($vehicleId === NULL) {
            return;
        }

        /* @var $vehicles \Nette\Forms\Controls\SelectBox */
        $vehicles = $form["vehicle_id"];

        if (!in_array($vehicleId, $vehicles->getItems())) {
            try {
                $vehicle = $this->model->getVehicle($vehicleId);
                $vehicles->setItems([$vehicle->getId() => $vehicle->getLabel()] + $vehicles->getItems());
                $vehicles->setDefaultValue($vehicleId);
            } catch (\Model\Travel\VehicleNotFoundException $exc) {
            }
        }
    }

    private function createCommand(ArrayHash $values): void
    {
        $this->model->addCommand(
            $this->unitId,
            isset($values->contract_id) ? (int)$values->contract_id : NULL,
            $this->createDriver($values),
            $values->vehicle_id,
            $values->purpose,
            $values->place,
            $values->passengers,
            (float)$values->fuel_price,
            (float)$values->amortization,
            $values->note,
            $values->type
        );

        $this->presenter->flashMessage("Cestovní příkaz byl založen.");
    }

    private function updateCommand(ArrayHash $values): void
    {
        $this->model->updateCommand(
            $this->commandId,
            isset($values->contract_id) ? (int)$values->contract_id : NULL,
            $this->createDriver($values),
            $values->vehicle_id,
            $values->purpose,
            $values->place,
            $values->passengers,
            (float)$values->fuel_price,
            (float)$values->amortization,
            $values->note,
            $values->type
        );

        $this->presenter->flashMessage("Cestovní příkaz byl upraven.");
    }

    private function createDriver(ArrayHash $values): ?Driver
    {
        return isset($values->contract_id)
            ? NULL
            : new Driver($values->driverName, $values->driverContact, $values->driverAddress);
    }

}
