<?php

namespace App\AccountancyModule\UnitAccountModule;

use App\AccountancyModule\Components\CashbookControl;
use App\AccountancyModule\Factories\ICashbookControlFactory;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Model\DTO\Cashbook\Chit;
use Model\EventEntity;

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

        if ( ! $this->isReadable) {
            $this->flashMessage('Nemáš oprávnění číst data jednotky', 'danger');
            $this->redirect('Default:');
        }

        /** @var EventEntity $eventEntity */
        $eventEntity = $this->context->getService('unitAccountService');

        $this->cashbookId = $eventEntity->chits->getCashbookIdFromSkautisId($this->aid);
    }

    public function renderDefault(int $aid) : void
    {
        $this->template->setParameters([
            'cashbookId' => $this->cashbookId->toInt(),
            'isCashbookEmpty' => $this->isCashbookEmpty(),
            'unitPairs' => $this->unitService->getReadUnits($this->user),
        ]);
    }

    protected function createComponentCashbook(): CashbookControl
    {
        return $this->cashbookFactory->create($this->cashbookId, $this->isEditable);
    }

    private function isCashbookEmpty(): bool
    {
        /** @var Chit[] $chits */
        $chits = $this->queryBus->handle(new ChitListQuery($this->cashbookId));

        return empty($chits);
    }

}
