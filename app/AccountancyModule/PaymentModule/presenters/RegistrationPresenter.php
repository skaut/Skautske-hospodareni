<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule;

use App\AccountancyModule\PaymentModule\Components\MassAddForm;
use App\AccountancyModule\PaymentModule\Factories\IMassAddFormFactory;
use Model\PaymentService;
use function array_keys;
use function array_slice;
use function intdiv;

class RegistrationPresenter extends BasePresenter
{
    /** @var string[] */
    protected $readUnits;

    /** @var PaymentService */
    private $model;

    /** @var IMassAddFormFactory */
    private $massAddFormFactory;

    /** @var int */
    private $id;

    private const STS_PRICE = 200;

    public function __construct(IMassAddFormFactory $massAddFormFactory, PaymentService $model)
    {
        parent::__construct();
        $this->model              = $model;
        $this->massAddFormFactory = $massAddFormFactory;
    }


    protected function startup() : void
    {
        parent::startup();
        $this->readUnits = $units = $this->unitService->getReadUnits($this->user);
        $this->template->setParameters([
            'unitPairs' =>$this->readUnits,
        ]);
    }

    /**
     * @param null $aid - NEZBYTNÝ PRO FUNKCI VÝBĚRU JINÉ JEDNOTKY
     */
    public function actionMassAdd(int $id, ?int $aid = null) : void
    {
        $this->id = $id;

        //ověření přístupu
        try {
            $list = $this->model->getPersonsFromRegistrationWithoutPayment(array_keys($this->readUnits), $id);
        } catch (\InvalidArgumentException $exc) {
            $this->flashMessage('Neoprávněný přístup ke skupině.', 'danger');
            $this->redirect('Payment:default');
            return;
        }

        $group = $this->model->getGroup($id);

        if ($group === null) {
            $this->flashMessage('Neplatný požadavek na přidání registračních plateb', 'danger');
            $this->redirect('Payment:default');
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
            'id'       => $id,
            'showForm' => ! empty($list),
        ]);
    }

    protected function createComponentMassAddForm() : MassAddForm
    {
        return $this->massAddFormFactory->create($this->id);
    }
}
