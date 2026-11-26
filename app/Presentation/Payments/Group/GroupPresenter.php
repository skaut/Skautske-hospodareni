<?php

declare(strict_types=1);

namespace App\Presentation\Payments\Group;

use App\Components\Factories\Payment\IGroupFormFactory;
use App\Components\Payment\GroupForm;
use App\Model\DTO\Payment\Group;
use App\Model\Payment\PaymentService;
use App\Presentation\Payments\PaymentsBasePresenter;
use Assert\Assertion;

final class GroupPresenter extends PaymentsBasePresenter
{
    private ?Group $group = null;
    private ?int $cloneSourceGroupId = null;

    public function __construct(private PaymentService $model, private IGroupFormFactory $groupFormFactory)
    {
        parent::__construct();
    }

    public function actionNewGroup(): void
    {
        $this->assertCanCreateGroup();
    }

    public function actionEdit(int $id): void
    {
        $group = $this->model->getGroup($id);

        if ($group === null || ! $this->canEditGroup($group)) {
            $this->flashMessage('Skupina nebyla nalezena', 'warning');
            $this->redirect('GroupList:');
        }

        $this->group = $group;
        $this->template->setParameters(['group' => $group]);
    }

    public function actionClone(int $id): void
    {
        $group = $this->model->getGroup($id);

        if (! $this->isEditable || $group === null || ! $this->canEditGroup($group)) {
            $this->flashMessage('Skupinu nelze klonovat.', 'warning');
            $this->redirect('GroupList:');
        }

        $this->cloneSourceGroupId = $id;
        $this->template->setParameters(['sourceGroup' => $group]);
    }

    protected function createComponentEditGroupForm(): GroupForm
    {
        $group = $this->group;

        Assertion::notNull($group);
        $unitId = $this->getCurrentUnitId();

        return $this->groupFormFactory->create($unitId, null, $group->getId());
    }

    protected function createComponentNewGroupForm(): GroupForm
    {
        // The form can be submitted through any action of this presenter, so it checks the rights itself.
        $this->assertCanCreateGroup();
        $unitId = $this->getCurrentUnitId();

        return $this->groupFormFactory->create($unitId, null);
    }

    protected function createComponentCloneGroupForm(): GroupForm
    {
        Assertion::notNull($this->cloneSourceGroupId);

        return $this->groupFormFactory->create(
            $this->getCurrentUnitId(),
            null,
            null,
            $this->cloneSourceGroupId,
        );
    }

    private function assertCanCreateGroup(): void
    {
        if ($this->isEditable) {
            return;
        }

        $this->denyAccess('Nemáte oprávnění zakládat skupiny plateb v této jednotce.');
    }
}
