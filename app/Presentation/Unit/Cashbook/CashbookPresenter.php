<?php

declare(strict_types=1);

namespace App\Presentation\Unit\Cashbook;

use App\Components\CashbookControl;
use App\Components\Factories\ICashbookControlFactory;
use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\Cashbook\PaymentMethod;
use App\Model\Cashbook\ReadModel\Queries\ActiveUnitCashbookQuery;
use App\Model\Cashbook\ReadModel\Queries\ChitListQuery;
use App\Model\Cashbook\ReadModel\Queries\UnitCashbookListQuery;
use App\Model\DTO\Cashbook\UnitCashbook;
use App\Presentation\Unit\Accessory\Components\ActivateCashbookDialog;
use App\Presentation\Unit\Accessory\Components\CreateCashbookDialog;
use App\Presentation\Unit\Accessory\Factories\IActivateCashbookDialogFactory;
use App\Presentation\Unit\Accessory\Factories\ICreateCashbookDialogFactory;
use App\Presentation\Unit\UnitBasePresenter;
use LogicException;

use function sprintf;

class CashbookPresenter extends UnitBasePresenter
{
    private CashbookId $cashbookId;

    public function __construct(
        private ICashbookControlFactory $cashbookFactory,
        private ICreateCashbookDialogFactory $createCashbookDialogFactory,
        private IActivateCashbookDialogFactory $activateCashbookDialogFactory,
    ) {
        parent::__construct();
    }

    protected function startup(): void
    {
        parent::startup();

        if ($this->isReadable) {
            return;
        }

        $this->flashMessage('Nemáš oprávnění číst data jednotky', 'danger');
        $this->redirect(':Dashboard:default');
    }

    public function handleCreateCashbook(): void
    {
        $dialog = $this['createCashbookDialog'];

        $dialog->open();
    }

    public function handleSelectActive(): void
    {
        $dialog = $this['activateCashbookDialog'];

        $dialog->open();
    }

    public function actionDefault(?int $unitId = null, ?int $year = null): void
    {
        if ($unitId === null) {
            $this->redirect('default', ['unitId' => $this->unitService->getUnitId(), 'year' => $year]);
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
            $this->redirect('default', ['unitId' => $this->unitId->toInt(), 'year' => $activeCashbook->getYear()]);
        }

        $cashbooks = $this->queryBus->handle(new UnitCashbookListQuery($this->unitId));

        $this->template->setParameters([
            'cashbooks' => $cashbooks,
            'year' => $year,
            'activeCashbook' => $activeCashbook,
        ]);

        foreach ($cashbooks as $cashbook) {
            if (! $cashbook instanceof UnitCashbook) {
                throw new LogicException('Assertion failed.');
            }
            if ($cashbook->getYear() === $year) {
                $this->cashbookId = $cashbook->getCashbookId();

                return;
            }
        }

        $this->flashMessage(sprintf('Pokladní kniha pro rok %d neexistuje', $year), 'danger');
        $this->redirect('default', ['unitId' => $this->unitId->toInt(), 'year' => $activeCashbook->getYear()]);
    }

    public function renderDefault(int $unitId): void
    {
        $this->template->setParameters([
            'cashbookId' => $this->cashbookId->toString(),
            'isCashbookEmpty' => $this->isCashbookEmpty(),
        ]);
    }

    /**
     * Do not allow direct access to action.
     * This is internal action used inside "default" action when there is no unit yet.
     */
    public function actionNoCashbook(int $unitId): void
    {
        $activeCashbook = $this->getActiveCashbook();

        if ($activeCashbook === null) {
            return;
        }

        $this->redirect('default', ['unitId' => $unitId, 'year' => $activeCashbook->getYear()]);
    }

    protected function createComponentActivateCashbookDialog(): ActivateCashbookDialog
    {
        return $this->activateCashbookDialogFactory->create($this->isEditable, $this->unitId);
    }

    protected function createComponentCreateCashbookDialog(): CreateCashbookDialog
    {
        $dialog = $this->createCashbookDialogFactory->create($this->isEditable, $this->unitId);

        $dialog->onSuccess[] = function (int $year): void {
            $this->redirect('default', ['unitId' => $this->unitId->toInt(), 'year' => $year]);
        };

        return $dialog;
    }

    protected function createComponentCashbook(): CashbookControl
    {
        return $this->cashbookFactory->create($this->cashbookId, $this->isEditable, $this->getCurrentUnitId());
    }

    private function isCashbookEmpty(): bool
    {
        $chits = $this->queryBus->handle(ChitListQuery::withMethod(PaymentMethod::CASH(), $this->cashbookId));

        return $chits === [];
    }

    private function getActiveCashbook(): ?UnitCashbook
    {
        return $this->queryBus->handle(new ActiveUnitCashbookQuery($this->unitId));
    }
}
