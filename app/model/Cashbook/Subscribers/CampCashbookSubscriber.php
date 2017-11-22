<?php

namespace Model\Cashbook\Subscribers;

use eGen\MessageBus\Bus\CommandBus;
use Model\Cashbook\Commands\Cashbook\UpdateCampCategoryTotal;
use Model\Cashbook\Events\ChitWasAdded;
use Model\Skautis\Mapper;

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

    /**
     * Update category total in Skautis for camp cashbook
     */
    public function chitWasAdded(ChitWasAdded $event): void
    {
        $id = $event->getCashbookId();

        if( ! $this->mapper->isCamp($id)) {
            return;
        }

        $this->commandBus->handle(new UpdateCampCategoryTotal($id, $event->getCategoryId()));
    }

}
