<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule\Components;

use App\AccountancyModule\Components\BaseControl;
use App\Forms\BaseForm;
use eGen\MessageBus\Bus\CommandBus;
use Model\Auth\IAuthorizator;
use Model\Auth\Resources\Unit;
use Model\DTO\Payment\Group;
use Model\Payment\Commands\Group\ChangeGroupUnits;
use Model\PaymentService;
use Model\UnitService;
use Nette\Application\BadRequestException;
use Nette\Utils\ArrayHash;
use function array_map;
use function array_replace;

class GroupUnitControl extends BaseControl
{
    /** @var bool @persistent */
    public $editation = false;

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
        $this->groupId      = $groupId;
        $this->commandBus   = $commandBus;
        $this->groups       = $groups;
        $this->units        = $units;
        $this->authorizator = $authorizator;
    }

    public function handleEdit() : void
    {
        $this->editation = true;
        $this->redrawControl();
    }

    public function handleCancel() : void
    {
        $this->editation = false;
        $this->redrawControl();
    }

    /**
     * @throws BadRequestException
     */
    public function render() : void
    {
        $group = $this->groups->getGroup($this->groupId);

        $unitNames = array_map(
            function (int $unitId) : string {
                return $this->units->getDetailV2($unitId)->getDisplayName();
            },
            $group->getUnitIds()
        );

        $this->template->setParameters([
            'unitNames'  => $unitNames,
            'editation' => $this->editation,
            'canEdit'   => $this->canEdit($group),
        ]);
        $this->template->setFile(__DIR__ . '/templates/GroupUnitControl.latte');
        $this->template->render();
    }

    /**
     * @throws BadRequestException
     */
    protected function createComponentForm() : BaseForm
    {
        $form = new BaseForm();

        $group = $this->groups->getGroup($this->groupId);

        $form->addMultiSelect('unitIds')
            ->setItems($this->buildUnitPairs($group->getUnitIds()))
            ->setDefaultValue($group->getUnitIds())
            ->setRequired('Musíte vybrat jednotku');

        $form->addButton('save')
            ->setAttribute('type', 'submit');

        $form->onSuccess[] = function ($form, ArrayHash $values) use ($group) : void {
            if (! $this->canEdit($group)) {
                $this->getPresenter()->flashMessage('Nemáte oprávnění pro změnu jednotky');
                $this->editation = false;
                $this->redrawControl();
                return;
            }
            $this->formSucceeded($values, $group->getId());
        };

        return $form;
    }

    private function formSucceeded(ArrayHash $values, int $groupId) : void
    {
        $this->commandBus->handle(new ChangeGroupUnits($groupId, $values->unitIds));
        $this->getPresenter()->flashMessage('Jednotka byla změněna', 'success');
        $this->editation = false;
        $this->redrawControl();
    }

    private function canEdit(Group $group) : bool
    {
        foreach ($group->getUnitIds() as $unitId) {
            if ($this->authorizator->isAllowed(Unit::EDIT, $unitId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param int[] $groupUnitIds
     * @return string[]
     */
    private function buildUnitPairs(array $groupUnitIds) : array
    {
        $officialUnitPairs = [];

        foreach ($groupUnitIds as $unitId) {
            $officialUnitId = $this->units->getOfficialUnitId($unitId);

            if (isset($officialUnitPairs[$officialUnitId])) {
                continue;
            }

            $officialUnit = $this->units->getDetailV2($officialUnitId);
            $subunitPairs = $this->units->getSubunitPairs($officialUnitId);

            $officialUnitPairs[] = [$officialUnitId => $officialUnit->getSortName()] + $subunitPairs;
        }

        return array_replace(...$officialUnitPairs);
    }
}
