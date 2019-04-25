<?php

declare(strict_types=1);

namespace App\AccountancyModule\EventModule;

use App\AccountancyModule\Components\CashbookControl;
use App\AccountancyModule\Factories\ICashbookControlFactory;
use Cake\Chronos\Date;
use Model\Auth\Resources\Event;
use Model\Cashbook\Cashbook\Amount;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\ChitBody;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\Cashbook\Recipient;
use Model\Cashbook\Category;
use Model\Cashbook\Commands\Cashbook\AddChitToCashbook;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Model\Cashbook\ReadModel\Queries\EventCashbookIdQuery;
use Model\Cashbook\ReadModel\Queries\EventParticipantBalanceQuery;
use Model\Cashbook\ReadModel\Queries\FinalCashBalanceQuery;
use Model\Cashbook\ReadModel\Queries\FinalRealBalanceQuery;
use Model\Event\Functions;
use Model\Event\ReadModel\Queries\EventFunctions;
use Model\Event\SkautisEventId;
use Money\Money;
use function assert;
use function is_float;

class CashbookPresenter extends BasePresenter
{
    /** @var ICashbookControlFactory */
    private $cashbookFactory;

    public function __construct(ICashbookControlFactory $cashbookFactory)
    {
        parent::__construct();
        $this->cashbookFactory = $cashbookFactory;
    }

    protected function startup() : void
    {
        parent::startup();
        $isDraft          = $this->event->ID_EventGeneralState === 'draft';
        $this->isEditable = $isDraft && $this->authorizator->isAllowed(Event::UPDATE_PARTICIPANT, $this->aid);
    }

    public function renderDefault(int $aid) : void
    {
        $incomeBalance    = $this->queryBus->handle(new EventParticipantBalanceQuery(new SkautisEventId($aid), $this->getCashbookId()));
        $finalBalance     = $this->queryBus->handle(new FinalCashBalanceQuery($this->getCashbookId()));
        $finalRealBalance = $this->queryBus->handle(new FinalRealBalanceQuery($this->getCashbookId()));

        assert(is_float($incomeBalance) && $finalBalance instanceof Money && $finalRealBalance instanceof Money);

        $this->template->setParameters(
            [
                'isCashbookEmpty' => $this->isCashbookEmpty(),
                'cashbookId' => $this->getCashbookId()->toString(),
                'isInMinus' => $finalBalance->isNegative(),
                'incomeBalance' => $incomeBalance,
                'isEditable' => $this->isEditable,
                'finalRealBalance' => $finalRealBalance,
            ]
        );
    }

    public function handleImportHpd(int $aid) : void
    {
        if (! $this->isEditable) {
            $this->flashMessage('Akce je uzavřena a nelze ji upravovat.', 'danger');
            if ($this->isAjax()) {
                $this->sendPayload();
            } else {
                $this->redirect('this');
            }
        }

        // @TODO move logic to specific command handler
        $totalPayment = $this->eventService->getParticipants()->getTotalPayment($this->aid);

        if ($totalPayment === 0.0) {
            $this->flashMessage('Nemáte žádné účastníky');
            $this->redirect('this');
        }

        $functions = $this->queryBus->handle(new EventFunctions(new SkautisEventId($aid)));
        $date      = $this->eventService->getEvent()->get($aid)->StartDate;

        assert($functions instanceof Functions);

        $accountant = $functions->getAccountant() !== null
            ? new Recipient($functions->getAccountant()->getName())
            : null;
        $amount     = new Amount((string) $totalPayment);
        $cashbookId = $this->getCashbookId();

        $this->commandBus->handle(
            new AddChitToCashbook(
                $cashbookId,
                new ChitBody(null, new Date($date), $accountant, 'účastnické příspěvky'),
                $amount,
                Category::EVENT_PARTICIPANTS_INCOME_CATEGORY_ID,
                PaymentMethod::CASH()
            )
        );

        $this->flashMessage('Účastníci byli importováni');
        $this->redirect('this');
    }

    protected function createComponentCashbook() : CashbookControl
    {
        return $this->cashbookFactory->create($this->getCashbookId(), $this->isEditable);
    }

    private function isCashbookEmpty() : bool
    {
        $chits = $this->queryBus->handle(ChitListQuery::withMethod(PaymentMethod::CASH(), $this->getCashbookId()));

        return $chits === [];
    }

    private function getCashbookId() : CashbookId
    {
        return $this->queryBus->handle(new EventCashbookIdQuery(new SkautisEventId($this->aid)));
    }
}
