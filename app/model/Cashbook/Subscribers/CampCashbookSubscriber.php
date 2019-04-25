<?php

declare(strict_types=1);

namespace Model\Cashbook\Subscribers;

use eGen\MessageBus\Bus\CommandBus;
use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\CashbookType;
use Model\Cashbook\Commands\Cashbook\UpdateCampCategoryTotals;
use Model\Cashbook\Events\ChitWasAdded;
use Model\Cashbook\Events\ChitWasUpdated;
use Model\Cashbook\ReadModel\Queries\CashbookQuery;
use Model\DTO\Cashbook\Cashbook;
use function assert;

/**
 * Update category total in Skautis for camp cashbook
 */
final class CampCashbookSubscriber
{
    /** @var CommandBus */
    private $commandBus;

    /** @var QueryBus */
    private $queryBus;

    public function __construct(CommandBus $commandBus, QueryBus $queryBus)
    {
        $this->commandBus = $commandBus;
        $this->queryBus   = $queryBus;
    }

    public function chitWasAdded(ChitWasAdded $event) : void
    {
        $id = $event->getCashbookId();

        if (! $this->isCamp($id)) {
            return;
        }

        $this->updateCategories($id);
    }

    public function chitWasUpdated(ChitWasUpdated $event) : void
    {
        $id = $event->getCashbookId();

        if (! $this->isCamp($id)) {
            return;
        }

        $this->updateCategories($id);
    }

    private function updateCategories(CashbookId $cashbookId) : void
    {
        $this->commandBus->handle(new UpdateCampCategoryTotals($cashbookId));
    }

    private function isCamp(CashbookId $cashbookId) : bool
    {
        $cashbook = $this->queryBus->handle(new CashbookQuery($cashbookId));

        assert($cashbook instanceof Cashbook);

        return $cashbook->getType()->equalsValue(CashbookType::CAMP);
    }
}
