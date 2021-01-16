<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule\RegistrationModule;

use App\AccountancyModule\PaymentModule\BasePresenter;
use App\AccountancyModule\PaymentModule\Components\MassAddForm;
use App\AccountancyModule\PaymentModule\Factories\IMassAddFormFactory;
use InvalidArgumentException;
use Model\PaymentService;

use function array_keys;
use function array_slice;
use function assert;
use function intdiv;

class AddMembersPresenter extends BasePresenter
{
    private int $id;

    /** @var string[] */
    protected array $readUnits;

    private PaymentService $model;

    private IMassAddFormFactory $massAddFormFactory;

    private const STS_PRICE = 200;

    public function __construct(IMassAddFormFactory $massAddFormFactory, PaymentService $model)
    {
        parent::__construct();
        $this->model              = $model;
        $this->massAddFormFactory = $massAddFormFactory;
    }

    protected function startup(): void
    {
        parent::startup();
        $this->readUnits = $this->unitService->getReadUnits($this->user);
    }

    /**
     * @param null $unitId - NEZBYTNÝ PRO FUNKCI VÝBĚRU JINÉ JEDNOTKY
     */
    public function actionDefault(int $id, ?int $unitId = null): void
    {
        $this->id = $id;

        //ověření přístupu
        try {
            $list = $this->model->getPersonsFromRegistrationWithoutPayment(array_keys($this->readUnits), $id);
        } catch (InvalidArgumentException $exc) {
            $this->flashMessage('Neoprávněný přístup ke skupině.', 'danger');
            $this->redirect(':Accountancy:Payment:GroupList:');

            return;
        }

        $group = $this->model->getGroup($id);

        if ($group === null) {
            $this->flashMessage('Neplatný požadavek na přidání registračních plateb', 'danger');
            $this->redirect('GroupList:default');
        }

        $form = $this['form'];
        assert($form instanceof MassAddForm);

        // performance issue - při větším množství zobrazených osob se nezpracuje formulář
        $list = array_slice($list, 0, 50);

        foreach ($list as $p) {
            $totalAmount = (float) $p['AmountTotal'];
            if ($totalAmount === 0.0) {
                continue;
            }

            $stsCount = intdiv((int) $p['AmountServices'], self::STS_PRICE);

            $form->addPerson(
                $p['ID_Person'],
                $p['emails'],
                $p['Person'],
                $totalAmount,
                $stsCount !== 0 ? $stsCount . 'x STS' : ''
            );
        }

        $this->template->setParameters([
            'group'    => $group,
            'showForm' => ! empty($list),
            'unitName' => $this->readUnits[$unitId ?? $this->unitId->toInt()],
        ]);
    }

    protected function createComponentForm(): MassAddForm
    {
        return $this->massAddFormFactory->create($this->id);
    }
}
