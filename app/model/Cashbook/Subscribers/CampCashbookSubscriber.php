<?php

namespace Model\Cashbook\Subscribers;

use eGen\MessageBus\Bus\CommandBus;
use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\CashbookType;
use Model\Cashbook\Commands\Cashbook\UpdateCampCategoryTotal;
use Model\Cashbook\Events\ChitWasAdded;
use Model\Cashbook\Events\ChitWasUpdated;
use Model\Cashbook\ReadModel\Queries\CashbookTypeQuery;

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
        $this->queryBus = $queryBus;
    }
    public function chitWasAdded(ChitWasAdded $event): void
    {
        $id = $event->getCashbookId();

        if( ! $this->isCamp($id)) {
            return;
        }

        $this->updateCategory($id, $event->getCategoryId());
    }

    public function chitWasUpdated(ChitWasUpdated $event): void
    {
        $id = $event->getCashbookId();

        if( ! $this->isCamp($id)) {
            return;
        }

        $categoryIds = [$event->getOldCategoryId(), $event->getNewCategoryId()];
        $categoryIds = array_unique($categoryIds); // Chit can be updated without category being changed

        foreach($categoryIds as $categoryId) {
            $this->updateCategory($id, $categoryId);
        }
    }

    private function updateCategory(CashbookId $cashbookId, int $categoryId): void
    {
        $this->commandBus->handle(new UpdateCampCategoryTotal($cashbookId, $categoryId));
    }

    private function isCamp(CashbookId $cashbookId): bool
    {
        /** @var CashbookType $actualType */
        $actualType = $this->queryBus->handle(new CashbookTypeQuery($cashbookId));

        return $actualType->equalsValue(CashbookType::CAMP);
    }

}
