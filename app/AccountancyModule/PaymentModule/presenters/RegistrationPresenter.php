<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule;

use App\AccountancyModule\PaymentModule\Components\GroupForm;
use App\AccountancyModule\PaymentModule\Components\MassAddForm;
use App\AccountancyModule\PaymentModule\Factories\IGroupFormFactory;
use App\AccountancyModule\PaymentModule\Factories\IMassAddFormFactory;
use Assert\Assertion;
use Cake\Chronos\Date;
use InvalidArgumentException;
use Model\Common\Registration;
use Model\Common\UnitId;
use Model\Payment\Group\SkautisEntity;
use Model\Payment\Group\Type;
use Model\Payment\ReadModel\Queries\RegistrationWithoutGroupQuery;
use Model\PaymentService;
use function array_keys;
use function array_slice;
use function intdiv;

class RegistrationPresenter extends BasePresenter
{
    private ?Registration $registration;

    private int $id;

    /** @var string[] */
    protected $readUnits;

    private PaymentService $model;

    private IMassAddFormFactory $massAddFormFactory;

    private IGroupFormFactory $groupFormFactory;

    private const STS_PRICE = 200;

    public function __construct(
        IMassAddFormFactory $massAddFormFactory,
        PaymentService $model,
        Factories\IGroupFormFactory $groupFormFactory
    ) {
        parent::__construct();
        $this->model              = $model;
        $this->massAddFormFactory = $massAddFormFactory;
        $this->groupFormFactory   = $groupFormFactory;
    }

    protected function startup() : void
    {
        parent::startup();
        $this->readUnits = $units = $this->unitService->getReadUnits($this->user);
        $this->template->setParameters([
            'unitPairs' =>$this->readUnits,
        ]);
    }

    public function actionNewGroup() : void
    {
        $registration = $this->queryBus->handle(
            new RegistrationWithoutGroupQuery(new UnitId($this->unitService->getUnitId()))
        );

        if ($registration === null) {
            $this->flashMessage('Nemáte založenou žádnou otevřenou registraci', 'warning');
            $this->redirect('GroupList:');
        }

        $this->registration = $registration;
        $this->template->setParameters(['registration' => $registration]);
    }

    /**
     * @param null $unitId - NEZBYTNÝ PRO FUNKCI VÝBĚRU JINÉ JEDNOTKY
     */
    public function actionMassAdd(int $id, ?int $unitId = null) : void
    {
        $this->id = $id;

        //ověření přístupu
        try {
            $list = $this->model->getPersonsFromRegistrationWithoutPayment(array_keys($this->readUnits), $id);
        } catch (InvalidArgumentException $exc) {
            $this->flashMessage('Neoprávněný přístup ke skupině.', 'danger');
            $this->redirect('GroupList:');

            return;
        }

        $group = $this->model->getGroup($id);

        if ($group === null) {
            $this->flashMessage('Neplatný požadavek na přidání registračních plateb', 'danger');
            $this->redirect('GroupList:default');
        }

        $form = $this['massAddForm'];
        /** @var MassAddForm $form */

        // performance issue - při větším množství zobrazených osob se nezpracuje formulář
        $list = array_slice($list, 0, 50);

        foreach ($list as $p) {
            $stsCount = intdiv((int) $p['AmountServices'], self::STS_PRICE);

            $form->addPerson(
                $p['ID_Person'],
                $p['emails'],
                $p['Person'],
                (float) $p['AmountTotal'],
                $stsCount !== 0 ? $stsCount . 'x STS' : ''
            );
        }

        $this->template->setParameters([
            'group'    => $group,
            'showForm' => ! empty($list),
        ]);
    }

    protected function createComponentMassAddForm() : MassAddForm
    {
        return $this->massAddFormFactory->create($this->id);
    }

    protected function createComponentNewGroupForm() : GroupForm
    {
        $registration = $this->registration;

        Assertion::notNull($registration);
        $unitId = $this->getCurrentUnitId();

        $form = $this->groupFormFactory->create(
            $unitId,
            new SkautisEntity($registration->getId(), Type::get(Type::REGISTRATION))
        );

        $form->fillName('Registrace ' . $registration->getYear());
        $form->fillDueDate(Date::createFromDate($registration->getYear(), 1, 15));

        return $form;
    }
}
