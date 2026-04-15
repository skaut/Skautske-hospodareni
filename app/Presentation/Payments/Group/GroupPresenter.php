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

    public function __construct(private PaymentService $model, private IGroupFormFactory $groupFormFactory)
    {
        parent::__construct();
    }

    public function actionNewGroup(): void
    {
        if ($this->isEditable) {
            return;
        }

        $this->setView('accessDenied');
        $this->template->setParameters(['message' => 'Nemáte oprávnění upravovat skupiny plateb.']);
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

    protected function createComponentEditGroupForm(): GroupForm
    {
        $group = $this->group;

        Assertion::notNull($group);
        $unitId = $this->getCurrentUnitId();

        return $this->groupFormFactory->create($unitId, null, $group->getId());
    }

    protected function createComponentNewGroupForm(): GroupForm
    {
        $unitId = $this->getCurrentUnitId();

        return $this->groupFormFactory->create($unitId, null);
    }
}
