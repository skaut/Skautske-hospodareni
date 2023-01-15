<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule\Components;

use App\AccountancyModule\Components\BaseControl;
use App\Forms\BaseForm;
use Model\Auth\IAuthorizator;
use Model\Auth\Resources\Unit as ResourceUnit;
use Model\Common\Services\CommandBus;
use Model\Common\Services\QueryBus;
use Model\Common\ShouldNotHappen;
use Model\DTO\Payment\Group;
use Model\Payment\Commands\Group\ChangeGroupUnits;
use Model\PaymentService;
use Model\Unit\ReadModel\Queries\UnitQuery;
use Model\Unit\ReadModel\Queries\UnitsDetailQuery;
use Model\Unit\Unit;
use Model\UnitService;
use Nette\Application\BadRequestException;
use Nette\Utils\ArrayHash;

use function array_keys;
use function array_map;
use function array_replace;
use function assert;
use function in_array;
use function sprintf;

class GroupUnitControl extends BaseControl
{
    public function __construct(private int $groupId, private CommandBus $commandBus, private PaymentService $groups, private UnitService $units, private IAuthorizator $authorizator, private QueryBus $queryBus)
    {
    }

    /** @throws BadRequestException */
    public function render(): void
    {
        $group = $this->getGroup($this->groupId);

        $unitNames = array_map(
            function (int $unitId): string {
                return $this->queryBus->handle(new UnitQuery($unitId))->getDisplayName();
            },
            $group->getUnitIds(),
        );

        $this->template->setParameters([
            'unitNames'  => $unitNames,
            'canEdit'   => $this->canEdit($group),
        ]);
        $this->template->setFile(__DIR__ . '/templates/GroupUnitControl.latte');
        $this->template->render();
    }

    /** @throws BadRequestException */
    protected function createComponentForm(): BaseForm
    {
        $form = new BaseForm();

        $group = $this->groups->getGroup($this->groupId);

        $form->addCheckboxList('unitIds')
            ->setItems($this->buildUnitPairs($group->getUnitIds()))
            ->setDefaultValue($group->getUnitIds())
            ->setRequired('Musíte vybrat jednotku');

        $form->addButton('save')
            ->setHtmlAttribute('type', 'submit');

        $form->onSuccess[] = function ($form, ArrayHash $values) use ($group): void {
            if (! $this->canEdit($group)) {
                $this->flashMessage('Nemáte oprávnění pro změnu jednotky', 'danger');
                $this->redrawControl();

                return;
            }

            $this->formSucceeded($values, $group->getId());
        };

        return $form;
    }

    private function formSucceeded(ArrayHash $values, int $groupId): void
    {
        $group = $this->getGroup($groupId);

        $this->commandBus->handle(new ChangeGroupUnits($groupId, $values->unitIds));

        $groupAfterChange = $this->getGroup($groupId);

        if ($group->getBankAccountId() !== null && $groupAfterChange->getBankAccountId() === null) {
            $this->flashMessage(
                'Bankovní účet byl odebrán, protože jej není možné pro tyto jednotky použít',
                'warning',
            );
        }

        if ($group->getOAuthId() !== null && $groupAfterChange->getOAuthId() === null) {
            $this->flashMessage(
                'Email byl odebrán, protože žádná z aktuálních jednotek k němu nemá přístup',
                'warning',
            );
        }

        $this->redrawControl();
    }

    private function canEdit(Group $group): bool
    {
        foreach ($group->getUnitIds() as $unitId) {
            if ($this->authorizator->isAllowed(ResourceUnit::EDIT, $unitId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param int[] $groupUnitIds
     *
     * @return string[]
     */
    private function buildUnitPairs(array $groupUnitIds): array
    {
        $officialUnitPairs = [];

        foreach ($groupUnitIds as $unitId) {
            $officialUnitId = $this->units->getOfficialUnitId($unitId);

            if (isset($officialUnitPairs[$officialUnitId])) {
                continue;
            }

            $officialUnit = $this->queryBus->handle(new UnitQuery($officialUnitId));
            assert($officialUnit instanceof Unit);
            $subunitPairs = $this->units->getSubunitPairs($officialUnitId, true);

            $officialUnitPairs[] = [$officialUnitId => $officialUnit->getDisplayName()] + $subunitPairs;
            if (in_array($unitId, [$officialUnitId, array_keys($subunitPairs)])) {
                continue;
            }

            $unitsDetail         = $this->queryBus->handle(new UnitsDetailQuery([$unitId]));
            $officialUnitPairs[] = [$unitId => $unitsDetail[$unitId]->getDisplayName()];
        }

        return array_replace(...$officialUnitPairs);
    }

    private function getGroup(int $groupId): Group
    {
        $group = $this->groups->getGroup($groupId);

        if ($group === null) {
            throw new ShouldNotHappen(sprintf(
                'Group used with %s should always exist',
                self::class,
            ));
        }

        return $group;
    }
}
