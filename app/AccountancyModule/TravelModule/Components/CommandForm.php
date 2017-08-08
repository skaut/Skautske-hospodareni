<?php

namespace App\AccountancyModule\TravelModule\Components;

use App\Forms\BaseForm;
use Dibi\Row;
use Model\Travel\Passenger;
use Model\TravelService;
use Model\Utils\MoneyFactory;
use Nette\Application\UI\Control;
use Nette\InvalidStateException;
use Nette\Utils\ArrayHash;
use Nette\Utils\Html;

class CommandForm extends Control
{

    /** @var int */
    private $unitId;

    /** @var int|NULL */
    private $commandId;

    /** @var TravelService */
    private $model;

    /** @var Row[] */
    private $transportTypes;

    /** @var callable[] */
    public $onSuccess = [];

    public function __construct(int $unitId, ?int $commandId, TravelService $model)
    {
        parent::__construct();
        $this->unitId = $unitId;
        $this->commandId = $commandId;
        $this->model = $model;
        $this->transportTypes = $this->model->getTravelTypes();
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

        $vehiclesWithFuel = array_filter($this->transportTypes, function ($t) { return $t->hasFuel; });
        $vehiclesWithFuel = array_map(function($t) { return $t->type; }, $vehiclesWithFuel);

        $form = new BaseForm();

        $form->addGroup();
        $form->addText("purpose", "Účel cesty*")
            ->setMaxLength(64)
            ->setAttribute("class", "form-control")
            ->addRule($form::FILLED, "Musíte vyplnit účel cesty.");
        $form->addMultiSelect("type", "Prostředek*", $this->prepareTranportTypeOptions())
            ->setAttribute("class", "combobox")
            ->setRequired("Vyberte alespoň jeden dopravní prostředek.")
            ->addCondition([\MyValidators::class, "hasSelectedAny"], $vehiclesWithFuel)
            ->toggle("vehicle");

        $form->addText("place", "Místo")
            ->setMaxLength(64)
            ->setAttribute("class", "form-control");
        $form->addText("fellowPassengers", "Spolucestující")
            ->setMaxLength(64)
            ->setAttribute("class", "form-control");
        $form->addText("note", "Poznámka")
            ->setMaxLength(64)
            ->setAttribute("class", "form-control");

        $form->addGroup("Cestující");
        $passenger = $form->addContainer("passenger");

        $form->addSelect("contract_id", "Smlouva", $contracts)
            ->setPrompt("Bez smlouvy")
            ->setAttribute("class", "form-control")
            ->setOption("id", "contractId")
            ->addCondition($form::BLANK)
            ->toggle("passengerName")
            ->toggle("passengerContact")
            ->toggle("passengerAddress");

        $passenger->addText("name", "Jméno cestujícího")
            ->setOption("id", "passengerName");
        $passenger->addText("contact", "Kontakt na cestujícího")
            ->setOption("id", "passengerContact");
        $passenger->addText("address", "Adresa cestujícího")
            ->setOption("id", "passengerAddress");

        $form->addGroup("Vozidlo")->setOption("container", Html::el("fieldset")->setAttribute("id", "vehicle"));
        $form->addSelect("vehicle_id", "Vozidlo*", $vehicles)
            ->setOption("id", "vehicle_id")
            ->setPrompt("Vyberte vozidlo")
            ->setAttribute("class", "form-control")
            ->addConditionOn($form['type'], [\MyValidators::class, "hasSelectedAny"], $vehiclesWithFuel)
            ->setRequired("Musíte vyplnit typ vozidla.");

        $form->addText("fuel_price", "Cena paliva za 1l*")
            ->setOption("id", "fuel_price")
            ->setAttribute("class", "form-control")
            ->addConditionOn($form['type'], [\MyValidators::class, "hasSelectedAny"], $vehiclesWithFuel)
            ->setRequired("Musíte vyplnit cenu paliva.")
            ->addRule($form::FLOAT, "Musíte zadat desetinné číslo.");

        $form->addText("amortization", "Opotřebení*")
            ->setOption("id", "amortization")
            ->setAttribute("class", "form-control")
            ->addConditionOn($form['type'], [\MyValidators::class, "hasSelectedAny"], $vehiclesWithFuel)
            ->setRequired("Musíte vyplnit opotřebení.")
            ->addRule($form::FLOAT, "Musíte zadat desetinné číslo.");

        $form->setCurrentGroup();
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

        $usedTypes = $this->model->getUsedTransportTypes($this->commandId);

        if(!empty($usedTypes)) {
            $form["type"]->setItems($this->prepareTranportTypeOptions($usedTypes));
            $form["type"]->setRequired(FALSE); // Even when nothing is selected, used types persist, so it's ok
        }

        $form->setDefaults([
            "contract_id" => $command->getPassenger()->getContractId(),
            "purpose" => $command->getPurpose(),
            "place" => $command->getPlace(),
            "fellowPassengers" => $command->getFellowPassengers(),
            "fuel_price" => MoneyFactory::toFloat($command->getFuelPrice()),
            "amortization" => MoneyFactory::toFloat($command->getAmortizationPerKm()),
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
            $this->createPassenger($values),
            $values->vehicle_id,
            $values->purpose,
            $values->place,
            $values->fellowPassengers,
            MoneyFactory::fromFloat((float)$values->fuel_price),
            MoneyFactory::fromFloat((float)$values->amortization),
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
            $this->createPassenger($values),
            $values->vehicle_id,
            $values->purpose,
            $values->place,
            $values->fellowPassengers,
            MoneyFactory::fromFloat((float)$values->fuel_price),
            MoneyFactory::fromFloat((float)$values->amortization),
            $values->note,
            $values->type
        );

        $this->presenter->flashMessage("Cestovní příkaz byl upraven.");
    }

    private function prepareTranportTypeOptions(array $disabledValues = []): array
    {
        $options = [];
        foreach($this->transportTypes as $value => $type) {
            $option = Html::el("option")
                ->setAttribute("value", $value)
                ->setHtml($type->label)
                ->setAttribute("disabled", in_array($value, $disabledValues, TRUE));
            $options[$value] = $option;
        }

        return $options;
    }

    private function createPassenger(ArrayHash $values): ?Passenger
    {
        return isset($values->contract_id)
            ? NULL
            : new Passenger($values->passenger->name, $values->passenger->contact, $values->passenger->address);
    }

}
