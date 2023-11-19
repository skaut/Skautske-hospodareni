<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule\RegistrationModule;

use App\AccountancyModule\PaymentModule\BasePresenter;
use App\AccountancyModule\PaymentModule\Components\GroupForm;
use App\AccountancyModule\PaymentModule\Factories;
use App\AccountancyModule\PaymentModule\Factories\IGroupFormFactory;
use Assert\Assertion;
use Cake\Chronos\ChronosDate;
use Model\Common\Registration;
use Model\Common\UnitId;
use Model\Payment\Group\SkautisEntity;
use Model\Payment\Group\Type;
use Model\Payment\ReadModel\Queries\RegistrationWithoutGroupQuery;

class CreateGroupPresenter extends BasePresenter
{
    private Registration $registration;

    private IGroupFormFactory $groupFormFactory;

    public function __construct(Factories\IGroupFormFactory $groupFormFactory)
    {
        parent::__construct();

        $this->groupFormFactory = $groupFormFactory;
    }

    public function actionDefault(): void
    {
        $registration = $this->queryBus->handle(
            new RegistrationWithoutGroupQuery(new UnitId($this->unitService->getUnitId())),
        );

        if ($registration === null) {
            $this->flashMessage('Nemáte založenou žádnou otevřenou registraci', 'warning');
            $this->redirect(':Accountancy:Payment:GroupList:');
        }

        $this->registration = $registration;
        $this->template->setParameters(['registration' => $registration]);
    }

    protected function createComponentForm(): GroupForm
    {
        $registration = $this->registration;

        Assertion::notNull($registration);
        $unitId = $this->getCurrentUnitId();

        $form = $this->groupFormFactory->create(
            $unitId,
            new SkautisEntity($registration->getId(), Type::get(Type::REGISTRATION)),
        );

        $form->fillName('Registrace ' . $registration->getYear());
        $form->fillDueDate(ChronosDate::createFromDate($registration->getYear(), 1, 15));

        return $form;
    }
}
