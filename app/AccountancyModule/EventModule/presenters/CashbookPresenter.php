<?php

declare(strict_types=1);

namespace App\AccountancyModule\EventModule;

use App\AccountancyModule\Components\CashbookControl;
use App\AccountancyModule\Factories\ICashbookControlFactory;
use Model\Auth\Resources\Event;
use Model\Cashbook\Cashbook\Amount;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\ChitBody;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\Cashbook\Recipient;
use Model\Cashbook\Category;
use Model\Cashbook\Commands\Cashbook\AddChitToCashbook;
use Model\Cashbook\ReadModel\Queries\CategoryListQuery;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Model\Cashbook\ReadModel\Queries\EventCashbookIdQuery;
use Model\Cashbook\ReadModel\Queries\EventParticipantBalanceQuery;
use Model\Cashbook\ReadModel\Queries\EventParticipantIncomeQuery;
use Model\Cashbook\ReadModel\Queries\FinalCashBalanceQuery;
use Model\Cashbook\ReadModel\Queries\FinalRealBalanceQuery;
use Model\DTO\Cashbook\ChitItem;
use Model\Event\Functions;
use Model\Event\ReadModel\Queries\EventFunctions;
use Model\Event\SkautisEventId;
use Money\Money;

use function assert;
use function is_float;

class CashbookPresenter extends BasePresenter
{
    public function __construct(private ICashbookControlFactory $cashbookFactory)
    {
        parent::__construct();
    }

    protected function startup(): void
    {
        parent::startup();
        $isDraft          = $this->event->getState() === 'draft';
        $this->isEditable = $isDraft && $this->authorizator->isAllowed(Event::UPDATE_PARTICIPANT, $this->aid);
    }

    public function renderDefault(int $aid): void
    {
        $incomeBalance    = $this->queryBus->handle(new EventParticipantBalanceQuery(new SkautisEventId($aid), $this->getCashbookId()));
        $finalBalance     = $this->queryBus->handle(new FinalCashBalanceQuery($this->getCashbookId()));
        $finalRealBalance = $this->queryBus->handle(new FinalRealBalanceQuery($this->getCashbookId()));

        assert(is_float($incomeBalance));
        assert($finalBalance instanceof Money);
        assert($finalRealBalance instanceof Money);

        $this->template->setParameters([
            'isCashbookEmpty' => $this->isCashbookEmpty(),
            'cashbookId' => $this->getCashbookId()->toString(),
            'isInMinus' => $finalBalance->isNegative(),
            'incomeBalance' => $incomeBalance,
            'isEditable' => $this->isEditable,
            'finalRealBalance' => $finalRealBalance,
        ]);
    }

    public function handleImportHpd(int $aid): void
    {
        if (! $this->isEditable) {
            $this->flashMessage('Akce je uzavřena a nelze ji upravovat.', 'danger');
            if ($this->isAjax()) {
                $this->sendPayload();
            } else {
                $this->redirect('this');
            }
        }

        $totalPayment = $this->queryBus->handle(new EventParticipantIncomeQuery(new SkautisEventId($this->aid)));

        if ($totalPayment === 0.0) {
            $this->flashMessage('Nemáte žádné příjmy od účastníků');
            $this->redirect('this');
        }

        $functions = $this->queryBus->handle(new EventFunctions(new SkautisEventId($this->aid)));

        assert($functions instanceof Functions);

        $accountant    = $functions->getAccountant() !== null
            ? new Recipient($functions->getAccountant()->getName())
            : null;
        $amount        = new Amount((string) $totalPayment);
        $cashbookId    = $this->getCashbookId();
        $categoriesDto = $this->queryBus->handle(new CategoryListQuery($this->getCashbookId()));

        $this->commandBus->handle(
            new AddChitToCashbook(
                $cashbookId,
                new ChitBody(null, $this->event->getStartDate(), $accountant),
                PaymentMethod::CASH(),
                [new ChitItem($amount, $categoriesDto[Category::EVENT_PARTICIPANTS_INCOME_CATEGORY_ID], 'účastnické příspěvky')],
            ),
        );

        $this->flashMessage('Účastníci byli importováni');
        $this->redirect('this');
    }

    protected function createComponentCashbook(): CashbookControl
    {
        return $this->cashbookFactory->create($this->getCashbookId(), $this->isEditable, $this->getCurrentUnitId());
    }

    private function isCashbookEmpty(): bool
    {
        $chits = $this->queryBus->handle(ChitListQuery::withMethod(PaymentMethod::CASH(), $this->getCashbookId()));

        return $chits === [];
    }

    private function getCashbookId(): CashbookId
    {
        return $this->queryBus->handle(new EventCashbookIdQuery(new SkautisEventId($this->aid)));
    }
}
