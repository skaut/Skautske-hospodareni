<?php

declare(strict_types=1);

namespace App\Presentation\Travel;

use App\BaseSectionPresenter;
use App\Model\Unit\Unit;
use App\Model\User\UserService;
use Nette\Security\SimpleIdentity;

use function array_keys;
use function array_map;
use function is_array;

class TravelBasePresenter extends BaseSectionPresenter
{
    protected Unit $officialUnit;

    protected function startup(): void
    {
        parent::startup();

        $this->officialUnit = $this->unitService->getOfficialUnit();
        $this->template->setParameters([
            'unit' => $this->officialUnit,
        ]);
    }

    protected function editableOnly(): void
    {
        if ($this->isEditable) {
            return;
        }

        $this->flashMessage('Akce je uzavřena a nelze ji upravovat.', 'danger');
        if ($this->isAjax()) {
            $this->sendPayload();
        } else {
            $this->redirect('Default:');
        }
    }

    /** @return int[] */
    protected function getReadableUnitIds(): array
    {
        $identity = $this->getUser()->getIdentity();
        if (! $identity instanceof SimpleIdentity) {
            return [];
        }

        $access = $identity->getData()['access'] ?? [];
        if (! is_array($access)) {
            return [];
        }

        $units = $access[UserService::ACCESS_READ] ?? [];
        if (! is_array($units)) {
            return [];
        }

        return array_map(static function ($unitId): int {
            return (int) $unitId;
        }, array_keys($units));
    }
}
