<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule;

use App\AccountancyModule\PaymentModule\Components\GroupForm;
use App\AccountancyModule\PaymentModule\Factories\IGroupFormFactory;
use Assert\Assertion;
use Model\DTO\Payment\Group;
use Model\PaymentService;

class GroupPresenter extends BasePresenter
{
    /** @var Group|null */
    private $group;

    private PaymentService $model;

    private IGroupFormFactory $groupFormFactory;

    public function __construct(PaymentService $model, IGroupFormFactory $groupFormFactory)
    {
        parent::__construct();
        $this->model            = $model;
        $this->groupFormFactory = $groupFormFactory;
    }

    public function actionNewGroup() : void
    {
        if ($this->isEditable) {
            return;
        }
        $this->setView('accessDenied');
        $this->template->setParameters(['message' => 'Nemáte oprávnění upravovat skupiny plateb.']);
    }

    public function actionEdit(int $id) : void
    {
        $group = $this->model->getGroup($id);

        if ($group === null || ! $this->canEditGroup($group)) {
            $this->flashMessage('Skupina nebyla nalezena', 'warning');
            $this->redirect('GroupList:');
        }

        $this->group = $group;
        $this->template->setParameters(['group' => $group]);
    }

    protected function createComponentEditGroupForm() : GroupForm
    {
        $group = $this->group;

        Assertion::notNull($group);
        $unitId = $this->getCurrentUnitId();

        return $this->groupFormFactory->create($unitId, null, $group->getId());
    }

    protected function createComponentNewGroupForm() : GroupForm
    {
        $unitId = $this->getCurrentUnitId();

        return $this->groupFormFactory->create($unitId, null);
    }
}
