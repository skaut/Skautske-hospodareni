<?php

declare(strict_types=1);

namespace App\AccountancyModule\Components\Participants;

use App\AccountancyModule\Components\BaseControl;
use App\Forms\BaseForm;
use eGen\MessageBus\Bus\QueryBus;
use Model\Common\UnitId;
use Model\DTO\Participant\NonMemberParticipant;
use Model\DTO\Participant\Participant;
use Model\Participant\ReadModel\Queries\PotentialParticipantListQuery;
use Model\Unit\ReadModel\Queries\SubunitListQuery;
use Model\Unit\ReadModel\Queries\UnitQuery;
use Model\Unit\Unit;
use function assert;

/**
 * @method void onSelect(int[] $personIds)
 * @method void onNonMemberAdd(NonMemberParticipant $participant)
 */
final class PersonPicker extends BaseControl
{
    /** @persistent */
    public ?string $unitId = null;

    /** @persistent */
    public bool $directMemberOnly = false;

    /** @var callable[] */
    public array $onSelect = [];

    /** @var callable[] */
    public array $onNonMemberAdd = [];

    /** @var Participant[] */
    private array $currentParticipants;

    private QueryBus $queryBus;

    private UnitId $userUnitId;

    /**
     * @param Participant[] $currentParticipants
     */
    public function __construct(
        UnitId $userUnitId,
        array $currentParticipants,
        QueryBus $queryBus
    ) {
        parent::__construct();
        $this->userUnitId          = $userUnitId;
        $this->currentParticipants = $currentParticipants;
        $this->queryBus            = $queryBus;
    }

    public function handleAdd(int $personId) : void
    {
        $this->onSelect([$personId]);

        $this->flashMessage('Účastník byl přidán', 'success');

        $this->redirect('this');
    }

    public function render() : void
    {
        $this->redrawControl(); // Always redraw

        $unitId = $this->selectedUnitId();
        $unit   = $this->queryBus->handle(new UnitQuery($unitId));
        assert($unit instanceof Unit);

        $this->template->setFile(__DIR__ . '/templates/PersonPicker.latte');
        $this->template->setParameters([
            'directMemberOnly' => $this->directMemberOnly,
            'unit' => $unit,
            'subunits' => $this->queryBus->handle(new SubunitListQuery(UnitId::fromInt($unitId))),
            'parentUnit' => $unit->getParentId() !== null
                ? $this->queryBus->handle(new UnitQuery($unit->getParentId()))
                : null,
        ]);
        $this->template->render();
    }

    private function selectedUnitId() : int
    {
        if ($this->unitId !== null) {
            return (int) $this->unitId;
        }

        return $this->userUnitId->toInt();
    }

    protected function createComponentMassAddForm() : BaseForm
    {
        $form = new BaseForm();

        $form->addCheckboxList('personIds', null, $this->getPotentialParticipants())
            ->setRequired(true);

        $form->addSubmit('send');

        $form->onSuccess[] = function ($_x, array $values) : void {
            $this->onSelect($values['personIds']);

            $this->redirect('this');
        };

        return $form;
    }

    protected function createComponentNonMemberParticipantForm() : BaseForm
    {
        $form = new BaseForm();

        $form->addText('firstName', 'Jméno')
            ->setRequired('Musíš vyplnit křestní jméno.');

        $form->addText('lastName', 'Příjmení')
            ->setRequired('Musíš vyplnit příjmení.');

        $form->addText('street', 'Ulice')
            ->setRequired('Musíš vyplnit ulici.');

        $form->addText('city', 'Město')
            ->setRequired('Musíš vyplnit město.');

        $form->addText('postcode', 'PSČ')
            ->setRequired('Musíš vyplnit PSČ.');

        $form->addText('nick', 'Přezdívka');

        $form->addDate('birthday', 'Dat. nar.');

        $form->addSubmit('send', 'Založit účastníka')
            ->setAttribute('class', 'btn btn-primary');

        $form->onSuccess[] = function (BaseForm $form) : void {
            $values = $form->getValues(true);

            $this->onNonMemberAdd(
                new NonMemberParticipant(
                    $values['firstName'],
                    $values['lastName'],
                    $values['nick'],
                    $values['birthday'],
                    $values['street'],
                    $values['city'],
                    (int) $values['postcode'],
                )
            );

            $this->flashMessage('Účastník byl přidán', 'success');
            $this->redirect('this');
        };

        return $form;
    }

    /**
     * @return array<int, string>
     */
    private function getPotentialParticipants() : array
    {
        $unitId = UnitId::fromInt($this->selectedUnitId());

        return $this->queryBus->handle(
            new PotentialParticipantListQuery($unitId, $this->directMemberOnly, $this->currentParticipants)
        );
    }
}
