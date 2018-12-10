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
use Model\Cashbook\ReadModel\Queries\EventParticipantIncomeQuery;
use Model\Cashbook\ReadModel\Queries\FinalCashBalanceQuery;
use Model\DTO\Cashbook\Chit;
use Model\Event\Functions;
use Model\Event\ReadModel\Queries\EventFunctions;
use Model\Event\SkautisEventId;
use Model\Utils\MoneyFactory;
use Money\Money;

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
        $incomeBalance = $this->queryBus->handle(new EventParticipantBalanceQuery(new SkautisEventId($aid), $this->getCashbookId()));

        /** @var Money $finalBalance */
        $finalBalance = $this->queryBus->handle(new FinalCashBalanceQuery($this->getCashbookId()));

        $this->template->setParameters(
            [
            'isCashbookEmpty' => $this->isCashbookEmpty(),
            'cashbookId' => $this->getCashbookId()->toString(),
            'isInMinus' => $finalBalance->isNegative(),
            'incomeBalance' => $incomeBalance,
            'isEditable' => $this->isEditable,
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

        /** @var Money $totalPayment */
        $totalPayment = $this->queryBus->handle(new EventParticipantIncomeQuery(new SkautisEventId($this->aid)));

        if ($totalPayment->isZero()) {
            $this->flashMessage('Nemáte žádné účastníky');
            $this->redirect('this');
        }

        /** @var Functions $functions */
        $functions  = $this->queryBus->handle(new EventFunctions(new SkautisEventId($aid)));
        $date       = $this->eventService->getEvent()->get($aid)->StartDate;
        $accountant = $functions->getAccountant() !== null
            ? new Recipient($functions->getAccountant()->getName())
            : null;
        $amount     = new Amount((string) MoneyFactory::toFloat($totalPayment));
        $cashbookId = $this->getCashbookId();

        $this->commandBus->handle(
            new AddChitToCashbook(
                $cashbookId,
                new ChitBody(null, new Date($date), $accountant, $amount, 'účastnické příspěvky'),
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
        /** @var Chit[] $chits */
        $chits = $this->queryBus->handle(ChitListQuery::withMethod(PaymentMethod::CASH(), $this->getCashbookId()));

        return empty($chits);
    }

    private function getCashbookId() : CashbookId
    {
        return $this->queryBus->handle(new EventCashbookIdQuery(new SkautisEventId($this->aid)));
    }
}
