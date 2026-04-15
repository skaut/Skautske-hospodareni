<?php

declare(strict_types=1);

namespace App\Presentation\Payments\RegistrationCreateGroup;

use App\Components\Factories\Payment\IGroupFormFactory;
use App\Components\Payment\GroupForm;
use App\Model\Common\Registration;
use App\Model\Common\UnitId;
use App\Model\Payment\Group\SkautisEntity;
use App\Model\Payment\Group\Type;
use App\Model\Payment\ReadModel\Queries\RegistrationWithoutGroupQuery;
use App\Presentation\Payments\PaymentsBasePresenter as BasePresenter;
use Assert\Assertion;
use Cake\Chronos\ChronosDate;

final class RegistrationCreateGroupPresenter extends BasePresenter
{
    private Registration $registration;

    public function __construct(private IGroupFormFactory $groupFormFactory)
    {
        parent::__construct();
    }

    public function actionDefault(): void
    {
        $registration = $this->queryBus->handle(
            new RegistrationWithoutGroupQuery(new UnitId($this->unitService->getUnitId())),
        );

        if ($registration === null) {
            $this->flashMessage('Nemáte založenou žádnou otevřenou registraci', 'warning');
            $this->redirect(':Payments:GroupList:');
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

        $form->fillName('Registrace '.$registration->getYear());
        $form->fillDueDate(ChronosDate::create($registration->getYear(), 1, 15));

        return $form;
    }
}
