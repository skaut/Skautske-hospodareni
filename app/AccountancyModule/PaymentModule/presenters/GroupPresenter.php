<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule;

use App\AccountancyModule\PaymentModule\Components\GroupForm;
use App\AccountancyModule\PaymentModule\Factories\IGroupFormFactory;
use Assert\Assertion;
use Model\DTO\Payment\Group;
use Model\PaymentService;
use function in_array;

class GroupPresenter extends BasePresenter
{
    /** @var Group|null */
    private $group;

    /** @var PaymentService */
    private $model;

    /** @var IGroupFormFactory */
    private $groupFormFactory;

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

        $this->flashMessage('Nemáte oprávnění upravovat skupiny plateb', 'danger');
        $this->redirect('Payment:default');
    }

    public function actionEdit(int $id) : void
    {
        if (! $this->isEditable) {
            $this->flashMessage('Nemáte oprávnění upravovat skupiny plateb', 'danger');
            $this->redirect('Payment:default');
        }

        $group  = $this->model->getGroup($id);
        $unitId = $this->getCurrentUnitId();

        Assertion::notNull($unitId);

        if ($group === null || ! in_array($unitId->toInt(), $group->getUnitIds(), true)) {
            $this->flashMessage('Skupina nebyla nalezena', 'warning');
            $this->redirect('Payment:default');
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
