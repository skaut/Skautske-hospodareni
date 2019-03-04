<?php

declare(strict_types=1);

namespace App\AccountancyModule\TravelModule\Components;

use App\Forms\BaseForm;
use App\MyValidators;
use Model\DTO\Travel\TravelType;
use Model\Travel\Passenger;
use Model\TravelService;
use Model\Utils\MoneyFactory;
use Nette\Application\UI\Control;
use Nette\Forms\Controls\SelectBox;
use Nette\InvalidStateException;
use Nette\Utils\ArrayHash;
use Nette\Utils\Html;
use function array_filter;
use function array_keys;
use function array_map;
use function in_array;

class CommandForm extends Control
{
    /** @var int */
    private $unitId;

    /** @var int|NULL */
    private $commandId;

    /** @var TravelService */
    private $model;

    /** @var TravelType[] */
    private $transportTypes;

    /** @var callable[] */
    public $onSuccess = [];

    public function __construct(int $unitId, ?int $commandId, TravelService $model)
    {
        parent::__construct();
        $this->unitId         = $unitId;
        $this->commandId      = $commandId;
        $this->model          = $model;
        $this->transportTypes = $this->model->getTransportTypes();
    }

    public function render() : void
    {
        $this['form']->render();
    }

    protected function createComponentForm() : BaseForm
    {
        $vehicles = $this->model->getVehiclesPairs($this->unitId);

        $vehiclesWithFuel = array_filter(
            $this->transportTypes,
            function (TravelType $t) {
                return $t->hasFuel();
            }
        );
        $vehiclesWithFuel = array_map(
            function (TravelType $t) {
                return $t->getShortcut();
            },
            $vehiclesWithFuel
        );

        $form = new BaseForm();

        $form->addGroup();
        $form->addText('purpose', 'Účel cesty*')
            ->setMaxLength(64)
            ->setAttribute('class', 'form-control')
            ->addRule($form::FILLED, 'Musíte vyplnit účel cesty.');
        $form->addMultiSelect('type', 'Prostředek*', $this->prepareTransportTypeOptions())
            ->setAttribute('class', 'combobox')
            ->setRequired('Vyberte alespoň jeden dopravní prostředek.')
            ->addCondition([MyValidators::class, 'hasSelectedAny'], $vehiclesWithFuel)
            ->toggle('vehicle');

        $form->addText('place', 'Místo')
            ->setMaxLength(64)
            ->setAttribute('class', 'form-control');
        $form->addText('fellowPassengers', 'Spolucestující')
            ->setMaxLength(64)
            ->setAttribute('class', 'form-control');
        $form->addText('note', 'Poznámka')
            ->setMaxLength(64)
            ->setAttribute('class', 'form-control');

        $form->addGroup('Cestující');
        $passenger = $form->addContainer('passenger');

        $form->addSelect('contract_id', 'Smlouva', $this->prepareContracts())
            ->setPrompt('Bez smlouvy')
            ->setAttribute('class', 'form-control')
            ->setOption('id', 'contractId')
            ->addCondition($form::BLANK)
            ->toggle('passengerName')
            ->toggle('passengerContact')
            ->toggle('passengerAddress');

        $passenger->addText('name', 'Jméno cestujícího')
            ->setOption('id', 'passengerName');
        $passenger->addText('contact', 'Kontakt na cestujícího')
            ->setOption('id', 'passengerContact');
        $passenger->addText('address', 'Adresa cestujícího')
            ->setOption('id', 'passengerAddress');

        $form->addGroup('Vozidlo')->setOption('container', Html::el('fieldset')->setAttribute('id', 'vehicle'));
        $form->addSelect('vehicle_id', 'Vozidlo*', $vehicles)
            ->setOption('id', 'vehicle_id')
            ->setPrompt('Vyberte vozidlo')
            ->setAttribute('class', 'form-control')
            ->addConditionOn($form['type'], [MyValidators::class, 'hasSelectedAny'], $vehiclesWithFuel)
            ->setRequired('Musíte vyplnit typ vozidla.');

        $form->addText('fuel_price', 'Cena paliva za 1l*')
            ->setOption('id', 'fuel_price')
            ->setAttribute('class', 'form-control')
            ->addConditionOn($form['type'], [MyValidators::class, 'hasSelectedAny'], $vehiclesWithFuel)
            ->setRequired('Musíte vyplnit cenu paliva.')
            ->addRule($form::FLOAT, 'Musíte zadat desetinné číslo.');

        $form->addText('amortization', 'Opotřebení*')
            ->setOption('id', 'amortization')
            ->setAttribute('class', 'form-control')
            ->addConditionOn($form['type'], [MyValidators::class, 'hasSelectedAny'], $vehiclesWithFuel)
            ->setRequired('Musíte vyplnit opotřebení.')
            ->addRule($form::FLOAT, 'Musíte zadat desetinné číslo.');

        $form->setCurrentGroup();
        $form->addSubmit('send', $this->commandId !== null ? 'Upravit' : 'Založit');

        $form->onSuccess[] = function (BaseForm $form) : void {
            if ($this->commandId === null) {
                $this->createCommand($form->getValues());
            } else {
                $this->updateCommand($form->getValues());
            }
            $this->onSuccess();
        };

        if ($this->commandId !== null) {
            $this->loadDefaultValues($form);
        }

        return $form;
    }

    private function loadDefaultValues(BaseForm $form) : void
    {
        $command = $this->model->getCommandDetail($this->commandId);

        if ($command === null) {
            throw new InvalidStateException('Travel command #' . $this->commandId . ' not found');
        }

        $usedTypes = $command->getTransportTypePairs();

        if (! empty($usedTypes)) {
            $form['type']->setItems($this->prepareTransportTypeOptions($usedTypes));
            $form['type']->setRequired(false); // Even when nothing is selected, used types persist, so it's ok
        }

        $contractId = $command->getPassenger()->getContractId();

        /** @var SelectBox $contracts */
        $contracts = $form['contract_id'];

        if ($contractId !== null && ! isset($contracts->getItems()[$contractId])) {
            $contracts->setItems($this->prepareContracts($contractId)); // Prepare list with missing contract
        }

        $form->setDefaults(
            [
            'contract_id' => $command->getPassenger()->getContractId(),
            'purpose' => $command->getPurpose(),
            'place' => $command->getPlace(),
            'fellowPassengers' => $command->getFellowPassengers(),
            'fuel_price' => MoneyFactory::toFloat($command->getFuelPrice()),
            'amortization' => MoneyFactory::toFloat($command->getAmortizationPerKm()),
            'note' => $command->getNote(),
            'type' => array_keys($command->getTransportTypePairs()),
            'passenger' => [
                'name' => $command->getPassenger()->getName(),
                'contact' => $command->getPassenger()->getContact(),
                'address' => $command->getPassenger()->getAddress(),
            ],
            ]
        );

        $vehicleId = $command->getVehicleId();

        if ($vehicleId === null) {
            return;
        }

        /** @var SelectBox $vehicles */
        $vehicles = $form['vehicle_id'];

        if (in_array($vehicleId, $vehicles->getItems())) {
            return;
        }

        $vehicle = $this->model->getVehicleDTO($vehicleId);
        if ($vehicle === null) {
            return;
        }

        $vehicles->setItems([$vehicle->getId() => $vehicle->getLabel()] + $vehicles->getItems());
        $vehicles->setDefaultValue($vehicleId);
    }

    private function createCommand(ArrayHash $values) : void
    {
        $this->model->addCommand(
            $this->unitId,
            isset($values->contract_id) ? (int) $values->contract_id : null,
            $this->createPassenger($values),
            $values->vehicle_id,
            $values->purpose,
            $values->place,
            $values->fellowPassengers,
            MoneyFactory::fromFloat((float) $values->fuel_price),
            MoneyFactory::fromFloat((float) $values->amortization),
            $values->note,
            $values->type,
            $this->getPresenter()->getUser()->getId()
        );

        $this->getPresenter()->flashMessage('Cestovní příkaz byl založen.');
    }

    private function updateCommand(ArrayHash $values) : void
    {
        $this->model->updateCommand(
            $this->commandId,
            isset($values->contract_id) ? (int) $values->contract_id : null,
            $this->createPassenger($values),
            $values->vehicle_id,
            $values->purpose,
            $values->place,
            $values->fellowPassengers,
            MoneyFactory::fromFloat((float) $values->fuel_price),
            MoneyFactory::fromFloat((float) $values->amortization),
            $values->note,
            $values->type
        );

        $this->getPresenter()->flashMessage('Cestovní příkaz byl upraven.');
    }

    /**
     * @param string[] $disabledValues
     * @return Html[]
     */
    private function prepareTransportTypeOptions(array $disabledValues = []) : array
    {
        $options = [];
        foreach ($this->transportTypes as $type) {
            $option                        = Html::el('option')
                ->setAttribute('value', $type->getShortcut())
                ->setHtml($type->getLabel())
                ->setAttribute('disabled', in_array($type->getShortcut(), $disabledValues, true));
            $options[$type->getShortcut()] = $option;
        }

        return $options;
    }

    /**
     * @return mixed[]
     */
    private function prepareContracts(?int $includeContractId = null) : array
    {
        $contracts = $this->model->getAllContractsPairs(
            $this->unitId,
            $includeContractId
        );

        if (! empty($contracts['past'])) {
            return ['platné' => $contracts['valid'], 'ukončené' => $contracts['past']];
        }

        return $contracts['valid'];
    }

    private function createPassenger(ArrayHash $values) : ?Passenger
    {
        return isset($values->contract_id)
            ? null
            : new Passenger($values->passenger->name, $values->passenger->contact, $values->passenger->address);
    }
}
