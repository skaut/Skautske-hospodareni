<?php

namespace Model\Cashbook\Subscribers;

use eGen\MessageBus\Bus\CommandBus;
use Model\Cashbook\Commands\Cashbook\UpdateCampCategoryTotal;
use Model\Cashbook\Events\ChitWasAdded;
use Model\Cashbook\Events\ChitWasUpdated;
use Model\Skautis\Mapper;

/**
 * Update category total in Skautis for camp cashbook
 */
final class CampCashbookSubscriber
{

    /** @var Mapper */
    private $mapper;

    /** @var CommandBus */
    private $commandBus;

    public function __construct(Mapper $mapper, CommandBus $commandBus)
    {
        $this->mapper = $mapper;
        $this->commandBus = $commandBus;
    }
    public function chitWasAdded(ChitWasAdded $event): void
    {
        $id = $event->getCashbookId();

        if( ! $this->mapper->isCamp($id)) {
            return;
        }

        $this->updateCategory($id, $event->getCategoryId());
    }

    public function chitWasUpdated(ChitWasUpdated $event): void
    {
        $id = $event->getCashbookId();

        if( ! $this->mapper->isCamp($id)) {
            return;
        }

        $categoryIds = [$event->getOldCategoryId(), $event->getNewCategoryId()];
        $categoryIds = array_unique($categoryIds); // Chit can be updated without category being changed

        foreach($categoryIds as $categoryId) {
            $this->updateCategory($id, $categoryId);
        }
    }

    private function updateCategory(int $cashbookId, int $categoryId): void
    {
        $this->commandBus->handle(new UpdateCampCategoryTotal($cashbookId, $categoryId));
    }

}
