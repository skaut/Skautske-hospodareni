<?php

declare(strict_types=1);

namespace App\AccountancyModule\EventModule;

use App\AccountancyModule\Components\CashbookControl;
use App\AccountancyModule\Factories\ICashbookControlFactory;
use Cake\Chronos\Date;
use Model\Auth\Resources\Event;
use Model\Cashbook\Cashbook\Amount;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\Recipient;
use Model\Cashbook\Category;
use Model\Cashbook\Commands\Cashbook\AddChitToCashbook;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Model\Cashbook\ReadModel\Queries\EventCashbookIdQuery;
use Model\Cashbook\ReadModel\Queries\FinalBalanceQuery;
use Model\DTO\Cashbook\Chit;
use Model\Event\Functions;
use Model\Event\ReadModel\Queries\EventFunctions;
use Model\Event\SkautisEventId;
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
        /**
 * @var Money $finalBalance
*/
        $finalBalance = $this->queryBus->handle(new FinalBalanceQuery($this->getCashbookId()));

        $this->template->setParameters(
            [
            'isCashbookEmpty' => $this->isCashbookEmpty(),
            'cashbookId' => $this->getCashbookId()->toString(),
            'isInMinus' => $finalBalance->isNegative(),
            'isEditable' => $this->isEditable,
            ]
        );
    }

    public function actionImportHpd(int $aid) : void
    {
        $this->editableOnly();

        // @TODO move logic to specific command handler
        $totalPayment = $this->eventService->getParticipants()->getTotalPayment($this->aid);

        if ($totalPayment === 0.0) {
            $this->flashMessage('Nemáte žádné účastníky');
            $this->redirect('this');
        }

        /**
 * @var Functions $functions
*/
        $functions  = $this->queryBus->handle(new EventFunctions(new SkautisEventId($aid)));
        $date       = $this->eventService->getEvent()->get($aid)->StartDate;
        $accountant = $functions->getAccountant() !== null
            ? new Recipient($functions->getAccountant()->getName())
            : null;

        $cashbookId = $this->getCashbookId();

        $this->commandBus->handle(
            new AddChitToCashbook(
                $cashbookId,
                null,
                new Date($date),
                $accountant,
                new Amount((string) $totalPayment),
                'účastnické příspěvky',
                Category::EVENT_PARTICIPANTS_INCOME_CATEGORY_ID
            )
        );

        $this->flashMessage('Účastníci byli importováni');
        $this->redirect('default', ['aid' => $aid]);
    }

    protected function createComponentCashbook() : CashbookControl
    {
        return $this->cashbookFactory->create($this->getCashbookId(), $this->isEditable);
    }

    private function isCashbookEmpty() : bool
    {
        /**
 * @var Chit[] $chits
*/
        $chits = $this->queryBus->handle(new ChitListQuery($this->getCashbookId()));

        return empty($chits);
    }

    private function getCashbookId() : CashbookId
    {
        return $this->queryBus->handle(new EventCashbookIdQuery(new SkautisEventId($this->aid)));
    }
}
