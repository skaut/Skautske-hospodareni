<?php

declare(strict_types=1);

namespace App\AccountancyModule\UnitAccountModule;

use App\AccountancyModule\Components\CashbookControl;
use App\AccountancyModule\Factories\ICashbookControlFactory;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Model\Cashbook\ReadModel\Queries\UnitCashbookListQuery;
use Model\DTO\Cashbook\Chit;
use Model\DTO\Cashbook\UnitCashbook;
use Nette\InvalidStateException;
use function count;

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

        /**
 * @var UnitCashbook[] $unitCashbooks
*/
        $unitCashbooks = $this->queryBus->handle(new UnitCashbookListQuery($this->aid));

        if (count($unitCashbooks) !== 1) {
            throw new InvalidStateException('This should not happen (unit should have always one cashbook)');
        }

        $this->cashbookId = $unitCashbooks[0]->getCashbookId();
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
        /**
 * @var Chit[] $chits
*/
        $chits = $this->queryBus->handle(ChitListQuery::withMethod(PaymentMethod::CASH(), $this->cashbookId));

        return empty($chits);
    }
}
