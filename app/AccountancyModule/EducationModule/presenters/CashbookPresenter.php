<?php

declare(strict_types=1);

namespace App\AccountancyModule\EducationModule;

use App\AccountancyModule\Components\CashbookControl;
use App\AccountancyModule\Factories\ICashbookControlFactory;
use Model\Auth\Resources\Education;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\MissingCategory;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Model\Cashbook\ReadModel\Queries\EducationCashbookIdQuery;
use Model\Cashbook\ReadModel\Queries\FinalCashBalanceQuery;
use Model\Cashbook\ReadModel\Queries\FinalRealBalanceQuery;
use Model\Event\SkautisEducationId;
use Money\Money;

use function assert;
use function count;

class CashbookPresenter extends BasePresenter
{
    public function __construct(
        private ICashbookControlFactory $cashbookFactory,
    ) {
        parent::__construct();
    }

    protected function startup(): void
    {
        parent::startup();
        $this->isEditable = $this->isEditable || $this->authorizator->isAllowed(Education::UPDATE_REAL_BUDGET_SPENDING, $this->aid);
    }

    public function renderDefault(int $aid): void
    {
        $finalBalance = $this->queryBus->handle(new FinalCashBalanceQuery($this->getCashbookId()));
        try {
            $finalRealBalance = $this->queryBus->handle(new FinalRealBalanceQuery($this->getCashbookId()));
            assert($finalRealBalance instanceof Money);
        } catch (MissingCategory) {
            $finalRealBalance = null;
        }

        assert($finalBalance instanceof Money);

        $this->template->setParameters([
            'isCashbookEmpty' => $this->isCashbookEmpty(),
            'cashbookId' => $this->getCashbookId()->toString(),
            'isInMinus' => $finalBalance->isNegative(),
            'isEditable' => $this->isEditable,
            'finalRealBalance' => $finalRealBalance,
        ]);
    }

    protected function createComponentCashbook(): CashbookControl
    {
        return $this->cashbookFactory->create(
            $this->getCashbookId(),
            $this->isEditable,
            $this->getCurrentUnitId(),
        );
    }

    private function getCashbookId(): CashbookId
    {
        return $this->queryBus->handle(new EducationCashbookIdQuery(new SkautisEducationId($this->aid)));
    }

    private function isCashbookEmpty(): bool
    {
        $chits = $this->queryBus->handle(ChitListQuery::withMethod(PaymentMethod::CASH(), $this->getCashbookId()));

        return count($chits) === 0;
    }
}
