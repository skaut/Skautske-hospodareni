<?php

declare(strict_types=1);

namespace App\AccountancyModule\UnitAccountModule;

use App\AccountancyModule\Components\CashbookControl;
use App\AccountancyModule\Factories\ICashbookControlFactory;
use App\AccountancyModule\UnitAccountModule\Components\ActivateCashbookDialog;
use App\AccountancyModule\UnitAccountModule\Components\CreateCashbookDialog;
use App\AccountancyModule\UnitAccountModule\Factories\IActivateCashbookDialogFactory;
use App\AccountancyModule\UnitAccountModule\Factories\ICreateCashbookDialogFactory;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\ReadModel\Queries\ActiveUnitCashbookQuery;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Model\Cashbook\ReadModel\Queries\UnitCashbookListQuery;
use Model\DTO\Cashbook\UnitCashbook;
use function assert;
use function sprintf;

class CashbookPresenter extends BasePresenter
{
    /** @var IActivateCashbookDialogFactory */
    private $activateCashbookDialogFactory;

    /** @var ICashbookControlFactory */
    private $cashbookFactory;

    /** @var ICreateCashbookDialogFactory */
    private $createCashbookDialogFactory;

    /** @var CashbookId */
    private $cashbookId;

    public function __construct(
        ICashbookControlFactory $cashbookFactory,
        ICreateCashbookDialogFactory $createCashbookDialogFactory,
        IActivateCashbookDialogFactory $activateCashbookDialogFactory
    ) {
        parent::__construct();
        $this->cashbookFactory               = $cashbookFactory;
        $this->createCashbookDialogFactory   = $createCashbookDialogFactory;
        $this->activateCashbookDialogFactory = $activateCashbookDialogFactory;
    }

    protected function startup() : void
    {
        parent::startup();

        if ($this->isReadable) {
            return;
        }

        $this->flashMessage('Nemáš oprávnění číst data jednotky', 'danger');
        $this->redirect(':Accountancy:Default:');
    }

    public function handleCreateCashbook() : void
    {
        $dialog = $this['createCashbookDialog'];

        $dialog->open();
    }

    public function handleSelectActive() : void
    {
        $dialog = $this['activateCashbookDialog'];

        $dialog->open();
    }

    public function actionDefault(?int $unitId = null, ?int $year = null) : void
    {
        $this->setLayout('layout2');
        if ($unitId === null) {
            $this->redirect('this', ['unitId' => $this->unitService->getUnitId(), 'year' => $year]);
        }

        $activeCashbook = $this->getActiveCashbook();

        $this->template->setParameters([
            'unitPairs' => $this->unitService->getReadUnits($this->user),
        ]);

        if ($activeCashbook === null) {
            $this->setView('noCashbook');

            return;
        }

        if ($year === null) {
            $this->redirect('this', [$this->unitId->toInt(), $activeCashbook->getYear()]);
        }

        $cashbooks = $this->queryBus->handle(new UnitCashbookListQuery($this->unitId));

        $this->template->setParameters([
            'cashbooks' => $cashbooks,
            'year' => $year,
            'activeCashbook' => $activeCashbook,
        ]);

        foreach ($cashbooks as $cashbook) {
            assert($cashbook instanceof UnitCashbook);

            if ($cashbook->getYear() === $year) {
                $this->cashbookId = $cashbook->getCashbookId();

                return;
            }
        }

        $this->flashMessage(sprintf('Pokladní kniha pro rok %d neexistuje', $year), 'danger');
        $this->redirect('this', [$this->unitId->toInt(), $activeCashbook->getYear()]);
    }

    public function renderDefault(int $unitId) : void
    {
        $this->template->setParameters([
            'cashbookId' => $this->cashbookId->toString(),
            'isCashbookEmpty' => $this->isCashbookEmpty(),
        ]);
    }

    /**
     * Do not allow direct access to action.
     * This is internal action used inside "default" action when there is no unit yet
     */
    public function actionNoCashbook(int $unitId) : void
    {
        $activeCashbook = $this->getActiveCashbook();

        if ($activeCashbook === null) {
            return;
        }

        $this->redirect('default', [$unitId, $activeCashbook->getYear()]);
    }

    protected function createComponentActivateCashbookDialog() : ActivateCashbookDialog
    {
        return $this->activateCashbookDialogFactory->create($this->isEditable, $this->unitId);
    }

    protected function createComponentCreateCashbookDialog() : CreateCashbookDialog
    {
        $dialog = $this->createCashbookDialogFactory->create($this->isEditable, $this->unitId);

        $dialog->onSuccess[] = function (int $year) : void {
            $this->redirect('default', [$this->unitId->toInt(), $year]);
        };

        return $dialog;
    }

    protected function createComponentCashbook() : CashbookControl
    {
        return $this->cashbookFactory->create($this->cashbookId, $this->isEditable, $this->getCurrentUnitId());
    }

    private function isCashbookEmpty() : bool
    {
        $chits = $this->queryBus->handle(ChitListQuery::withMethod(PaymentMethod::CASH(), $this->cashbookId));

        return $chits === [];
    }

    private function getActiveCashbook() : ?UnitCashbook
    {
        return $this->queryBus->handle(new ActiveUnitCashbookQuery($this->unitId));
    }
}
