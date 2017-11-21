<?php

namespace Model\Cashbook\Subscribers;

use Model\Cashbook\Events\ChitWasAdded;
use Model\Cashbook\ObjectType;
use Model\Cashbook\Repositories\ICashbookRepository;
use Model\EventEntity;
use Model\Skautis\Mapper;

final class CampCashbookSubscriber
{

    /** @var Mapper */
    private $mapper;

    /** @var ICashbookRepository */
    private $cashbooks;

    /** @var EventEntity */
    private $eventEntity;

    public function __construct(Mapper $objects, EventEntity $eventEntity, ICashbookRepository $cashbooks)
    {
        $this->mapper = $objects;
        $this->eventEntity = $eventEntity;
        $this->cashbooks = $cashbooks;
    }

    public function chitWasAdded(ChitWasAdded $event): void
    {
        $id = $event->getCashbookId();

        if( ! $this->mapper->isCamp($id)) {
            return;
        }

        $cashbook = $this->cashbooks->find($id);
        $skautisId = $this->mapper->getSkautisId($id, ObjectType::CAMP);
        $categoryId = $event->getCategoryId();

        $this->eventEntity->chits->updateCategory(
            $skautisId,
            $categoryId,
            $cashbook->getTotalForCategory($categoryId)
        );
    }

}
