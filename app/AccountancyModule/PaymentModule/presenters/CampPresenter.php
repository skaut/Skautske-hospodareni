<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule;

use App\AccountancyModule\PaymentModule\Components\GroupForm;
use App\AccountancyModule\PaymentModule\Components\MassAddForm;
use App\AccountancyModule\PaymentModule\Factories\IGroupFormFactory;
use App\AccountancyModule\PaymentModule\Factories\IMassAddFormFactory;
use Assert\Assertion;
use Cake\Chronos\Date;
use Model\Common\UnitId;
use Model\DTO\Participant\Participant;
use Model\Event\Camp;
use Model\Event\ReadModel\Queries\CampListQuery;
use Model\EventEntity;
use Model\Payment\Group\SkautisEntity;
use Model\Payment\Group\Type;
use Model\PaymentService;
use function array_filter;
use function assert;
use function in_array;

class CampPresenter extends BasePresenter
{
    /** @var string[] */
    protected $readUnits;

    /** @var PaymentService */
    private $model;

    /** @var EventEntity */
    protected $campService;

    /** @var IMassAddFormFactory */
    private $massAddFormFactory;

    /** @var int */
    private $id;

    /** @var Camp|null */
    private $camp;

    /** @var IGroupFormFactory */
    private $groupFormFactory;

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
        $this->campService                  = $this->getContext()->getService('campService');
    }

    public function actionDefault() : void
    {
        $this->template->setParameters(['camps' => $this->getCampsWithoutGroup()]);
    }

    public function actionNewGroup(int $campId) : void
    {
        $camps = $this->getCampsWithoutGroup();

        if (! $this->isEditable || ! isset($camps[$campId])) {
            $this->flashMessage('Pro tento tábor není možné vytvořit skupinu plateb', 'danger');
            $this->redirect('default');
        }

        $this->camp = $camps[$campId];
        $this->template->setParameters(['camp' => $this->camp]);
    }

    /**
     * @param null $aid - NEZBYTNÝ PRO FUNKCI VÝBĚRU JINÉ JEDNOTKY
     */
    public function actionMassAdd(int $id, ?int $aid = null) : void
    {
        $this->id = $id;
        $group    = $this->model->getGroup($id);

        if ($group === null || ! $this->isEditable) {
            $this->flashMessage('Neoprávněný přístup ke skupině.', 'danger');
            $this->redirect('Payment:default');
        }

        if ($group->getSkautisId() === null) {
            $this->flashMessage('Neplatné propojení skupiny plateb s táborem.', 'warning');
            $this->redirect('Default:');
        }

        $participants = $this->campService->getParticipants()->getAll($group->getSkautisId());

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
                $this->model->getPersonEmails($p->getPersonId()),
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
        Assertion::notNull($this->camp);

        $form = $this->groupFormFactory->create(
            new UnitId($this->getCurrentUnitId()),
            new SkautisEntity($this->camp->getId()->toInt(), Type::CAMP())
        );

        $form->fillName($this->camp->getDisplayName());

        return $form;
    }

    /**
     * @return Camp[]
     */
    private function getCampsWithoutGroup() : array
    {
        $campWithGroupIds = $this->model->getCampIds();

        $camps = [];

        foreach ($this->queryBus->handle(new CampListQuery(Date::today()->year)) as $camp) {
            assert($camp instanceof Camp);

            if (in_array($camp->getId()->toInt(), $campWithGroupIds, true)) {
                continue;
            }

            $camps[$camp->getId()->toInt()] = $camp;
        }

        return $camps;
    }
}
