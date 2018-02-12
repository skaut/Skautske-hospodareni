<?php

namespace App\AccountancyModule\PaymentModule\Components;

use Model\Auth\IAuthorizator;
use Model\Auth\Resources\Unit;
use App\AccountancyModule\Components\BaseControl;
use App\Forms\BaseForm;
use eGen\MessageBus\Bus\CommandBus;
use Model\Payment\Commands\Group\ChangeGroupUnit;
use Model\PaymentService;
use Model\UnitService;
use Nette\Utils\ArrayHash;

class GroupUnitControl extends BaseControl
{

    /** @var bool @persistent */
    public $editation = FALSE;

    /** @var int */
    private $groupId;

    /** @var CommandBus */
    private $commandBus;

    /** @var PaymentService */
    private $groups;

    /** @var UnitService */
    private $units;

    /** @var IAuthorizator */
    private $authorizator;

    public function __construct(int $groupId, CommandBus $commandBus, PaymentService $groups, UnitService $units, IAuthorizator $authorizator)
    {
        parent::__construct();
        $this->groupId = $groupId;
        $this->commandBus = $commandBus;
        $this->groups = $groups;
        $this->units = $units;
        $this->authorizator = $authorizator;
    }

    public function handleEdit(): void
    {
        $this->editation = TRUE;
        $this->redrawControl();
    }

    public function handleCancel(): void
    {
        $this->editation = FALSE;
        $this->redrawControl();
    }

    /**
     * @throws \Nette\Application\BadRequestException
     */
    public function render(): void
    {
        $group = $this->groups->getGroup($this->groupId);
        $unit = $this->units->getDetailV2($group->getUnitId());

        $this->template->unitName = $unit->getDisplayName();
        $this->template->editation = $this->editation;
        $this->template->canEdit = $this->canEdit($group->getUnitId());
        $this->template->setFile(__DIR__ . '/templates/GroupUnitControl.latte');
        $this->template->render();
    }

    /**
     * @throws \Nette\Application\BadRequestException
     */
    protected function createComponentForm(): BaseForm
    {
        $form = new BaseForm();

        $group = $this->groups->getGroup($this->groupId);
        $officialUnitId = $this->units->getOfficialUnitId($group->getUnitId());
        $officialUnit = $this->units->getDetailV2($officialUnitId);

        $pairs = [
            $officialUnitId => $officialUnit->getSortName(),
        ];
        $pairs = $pairs + $this->units->getSubunitPairs($officialUnitId);

        $form->addSelect('unitId')
            ->setItems($pairs)
            ->setDefaultValue($group->getUnitId())
            ->setRequired('Musíte vybrat jednotku');

        $form->addButton('save')
            ->setAttribute('type', 'submit');

        $form->onSuccess[] = function($form, ArrayHash $values) use($group) {
            if(!$this->canEdit($group->getUnitId())) {
                $this->getPresenter()->flashMessage('Nemáte oprávnění pro změnu jednotky');
                $this->editation = FALSE;
                $this->redrawControl();
                return;
            }
            $this->formSucceeded($values, $group->getId());
        };

        return $form;
    }

    private function formSucceeded(ArrayHash $values, int $groupId): void
    {
        $this->commandBus->handle(new ChangeGroupUnit($groupId, $values->unitId));
        $this->getPresenter()->flashMessage('Jednotka byla změněna', 'success');
        $this->editation = FALSE;
        $this->redrawControl();
    }

    private function canEdit(int $unitId): bool
    {
        return $this->authorizator->isAllowed(Unit::EDIT, $this->units->getOfficialUnitId($unitId));
    }

}
