<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule;

use App\AccountancyModule\PaymentModule\Components\GroupForm;
use App\AccountancyModule\PaymentModule\Components\MassAddForm;
use App\AccountancyModule\PaymentModule\Factories\IGroupFormFactory;
use App\AccountancyModule\PaymentModule\Factories\IMassAddFormFactory;
use Assert\Assertion;
use Cake\Chronos\Date;
use Model\Cashbook\ReadModel\Queries\EducationParticipantListQuery;
use Model\DTO\Participant\Participant;
use Model\Event\Education;
use Model\Event\SkautisEducationId;
use Model\Payment\Group\SkautisEntity;
use Model\Payment\ReadModel\Queries\EducationsWithoutGroupQuery;
use Model\Payment\ReadModel\Queries\MemberEmailsQuery;
use Model\PaymentService;
use function array_filter;
use function in_array;

class EducationPresenter extends BasePresenter
{
    /** @var string[] */
    protected array $readUnits;

    private PaymentService $model;

    private IMassAddFormFactory $massAddFormFactory;

    private int $id;

    private ?Education $education;

    private IGroupFormFactory $groupFormFactory;

    public function __construct(
        PaymentService $model,
        IMassAddFormFactory $massAddFormFactory,
        IGroupFormFactory $groupFormFactory
    ) {
        parent::__construct();
        $this->model              = $model;
        $this->massAddFormFactory = $massAddFormFactory;
        $this->groupFormFactory   = $groupFormFactory;
    }

    protected function startup() : void
    {
        parent::startup();
        $this->template->setParameters([
            'unitPairs' => $this->readUnits = $units = $this->unitService->getReadUnits($this->user),
        ]);
    }

    public function actionDefault() : void
    {
        $this->template->setParameters(['educations' => $this->getEducationsWithoutGroup()]);
    }

    public function actionNewGroup(int $educationId) : void
    {
        $educations = $this->getEducationsWithoutGroup();

        if (! $this->isEditable || ! isset($educations[$educationId])) {
            $this->flashMessage('Pro tento tábor není možné vytvořit skupinu plateb', 'danger');
            $this->redirect('default');
        }

        $this->education = $educations[$educationId];
        $this->template->setParameters(['education' => $this->education]);
    }

    /**
     * @param null $unitId - NEZBYTNÝ PRO FUNKCI VÝBĚRU JINÉ JEDNOTKY
     */
    public function actionMassAdd(int $id, ?int $unitId = null) : void
    {
        $this->id = $id;
        $group    = $this->model->getGroup($id);

        if ($group === null || ! $this->isEditable) {
            $this->flashMessage('Neoprávněný přístup ke skupině.', 'danger');
            $this->redirect('GroupList:');
        }

        if ($group->getSkautisId() === null) {
            $this->flashMessage('Neplatné propojení skupiny plateb se vzdělávací akcí.', 'warning');
            $this->redirect('Default:');
        }

        $participants = $this->queryBus->handle(
            new EducationParticipantListQuery(
                new SkautisEducationId($group->getSkautisId())
            )
        );

        $form = $this['massAddForm'];
        /** @var MassAddForm $form */

        $personsWithPayment = $this->model->getPersonsWithActivePayment($id);

        $participants = array_filter(
            $participants,
            function (Participant $p) use ($personsWithPayment) {
                return ! in_array($p->getPersonId(), $personsWithPayment, true);
            }
        );

        foreach ($participants as $p) {
            $amount = $p->getPayment();
            $form->addPerson(
                $p->getPersonId(),
                $this->queryBus->handle(new MemberEmailsQuery($p->getPersonId())),
                $p->getDisplayName(),
                $amount === 0.0 ? null : $amount
            );
        }

        $this->template->setParameters([
            'group'    => $group,
            'showForm' => ! empty($participants),
        ]);
    }

    protected function createComponentMassAddForm() : MassAddForm
    {
        return $this->massAddFormFactory->create($this->id);
    }

    protected function createComponentNewGroupForm() : GroupForm
    {
        Assertion::notNull($this->education);
        $unitId = $this->getCurrentUnitId();

        $form = $this->groupFormFactory->create(
            $unitId,
            SkautisEntity::fromEducationId($this->education->getId())
        );

        $form->fillName($this->education->getDisplayName());

        return $form;
    }

    /**
     * @return Education[]
     */
    private function getEducationsWithoutGroup() : array
    {
        return $this->queryBus->handle(new EducationsWithoutGroupQuery(Date::today()->year));
    }
}
