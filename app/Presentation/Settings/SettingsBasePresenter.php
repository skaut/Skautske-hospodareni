<?php

declare(strict_types=1);

namespace App\Presentation\Settings;

use App\BaseSectionPresenter;
use App\Model\User\ReadModel\Queries\ActiveSkautisRoleQuery;
use App\Model\User\ReadModel\Queries\EditableUnitsQuery;

use function array_keys;
use function in_array;

abstract class SettingsBasePresenter extends BaseSectionPresenter
{
    protected bool $isEditable = false;

    /** @var int[] */
    private array $editableUnits = [];

    protected function startup(): void
    {
        parent::startup();

        $readableUnits = $this->unitService->getReadUnits($this->getUser());
        $role = $this->queryBus->handle(new ActiveSkautisRoleQuery());
        $this->editableUnits = array_keys($this->queryBus->handle(new EditableUnitsQuery($role)));

        $this->isEditable = in_array($this->unitId->toInt(), $this->editableUnits, true);

        if (isset($readableUnits[$this->unitId->toInt()])) {
            return;
        }

        $this->setView('accessDenied');
    }

    /** @return int[] */
    protected function getEditableUnits(): array
    {
        return $this->editableUnits;
    }

    protected function setSettingsTemplateParameters(): void
    {
        $this->template->setParameters([
            'unitId' => $this->unitId->toInt(),
            'isEditable' => $this->isEditable,
        ]);
    }
}
