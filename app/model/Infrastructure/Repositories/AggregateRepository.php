<?php

declare(strict_types=1);

namespace Model\Infrastructure\Repositories;

use Doctrine\ORM\EntityManager;
use eGen\MessageBus\Bus\EventBus;
use Model\Common\Aggregate;

abstract class AggregateRepository
{
    /** @var EntityManager */
    private $entityManager;

    /** @var EventBus */
    private $eventBus;


    public function __construct(EntityManager $entityManager, EventBus $eventBus)
    {
        $this->entityManager = $entityManager;
        $this->eventBus      = $eventBus;
    }

    protected function getEntityManager() : EntityManager
    {
        return $this->entityManager;
    }

    protected function saveAndDispatchEvents(Aggregate $aggregate) : void
    {
        $events = $aggregate->extractEventsToDispatch();

        if (empty($events)) {
            $this->persist($aggregate);
            return;
        }

        $this->entityManager->transactional(function () use ($aggregate, $events) : void {
            $this->persist($aggregate);
            foreach ($events as $event) {
                $this->eventBus->handle($event);
            }
        });
    }

    private function persist(Aggregate $aggregate) : void
    {
        $this->entityManager->persist($aggregate);
        $this->entityManager->flush();
    }
}
