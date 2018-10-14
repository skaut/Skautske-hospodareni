<?php

declare(strict_types=1);

namespace App\AccountancyModule\UnitAccountModule;

use App\AccountancyModule\Components\CashbookControl;
use App\AccountancyModule\Factories\ICashbookControlFactory;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\ReadModel\Queries\ActiveUnitCashbookQuery;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Model\Cashbook\ReadModel\Queries\UnitCashbookListQuery;
use Model\Common\UnitId;
use Model\DTO\Cashbook\Chit;
use Model\DTO\Cashbook\UnitCashbook;
use Nette\InvalidStateException;
use function count;
use function sprintf;

class CashbookPresenter extends BasePresenter
{
    /** @var ICashbookControlFactory */
    private $cashbookFactory;

    /** @var CashbookId */
    private $cashbookId;

    public function __construct(ICashbookControlFactory $cashbookFactory)
    {
        parent::__construct();
        $this->cashbookFactory = $cashbookFactory;
    }

    protected function startup() : void
    {
        parent::startup();

        if (! $this->isReadable) {
            $this->flashMessage('Nemáš oprávnění číst data jednotky', 'danger');
            $this->redirect('Default:');
        }
    }

    public function actionDefault(int $aid, ?int $year = null) : void
    {
        /** @var UnitCashbook $activeCashbook */
        $activeCashbook = $this->queryBus->handle(new ActiveUnitCashbookQuery(new UnitId($this->aid)));

        if ($year === null) {
            $this->redirect('this', [$aid, $activeCashbook->getYear()]);
        }

        /** @var UnitCashbook[] $cashbooks */
        $cashbooks = $this->queryBus->handle(new UnitCashbookListQuery($this->aid));

        $this->template->setParameters(['cashbooks' => $cashbooks]);

        foreach ($cashbooks as $cashbook) {
            if ($cashbook->getYear() === $year) {
                $this->cashbookId = $cashbook->getCashbookId();
                return;
            }
        }

        $this->flashMessage(sprintf('Pokladní kniha pro rok %d neexistuje', $year), 'danger');
        $this->redirect('this', [$aid, $activeCashbook->getYear()]);
    }

    public function renderDefault(int $aid) : void
    {
        $this->template->setParameters(
            [
            'cashbookId' => $this->cashbookId->toString(),
            'isCashbookEmpty' => $this->isCashbookEmpty(),
            'unitPairs' => $this->unitService->getReadUnits($this->user),
            ]
        );
    }

    protected function createComponentCashbook() : CashbookControl
    {
        return $this->cashbookFactory->create($this->cashbookId, $this->isEditable);
    }

    private function isCashbookEmpty() : bool
    {
        /** @var Chit[] $chits */
        $chits = $this->queryBus->handle(ChitListQuery::withMethod(PaymentMethod::CASH(), $this->cashbookId));

        return empty($chits);
    }
}
