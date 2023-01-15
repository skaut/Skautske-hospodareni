<?php

declare(strict_types=1);

namespace Model\Infrastructure\Repositories;

use Doctrine\ORM\EntityManager;
use Model\Common\Aggregate;
use Model\Common\Services\EventBus;

abstract class AggregateRepository
{
    public function __construct(private EntityManager $entityManager, private EventBus $eventBus)
    {
    }

    protected function getEntityManager(): EntityManager
    {
        return $this->entityManager;
    }

    protected function saveAndDispatchEvents(Aggregate $aggregate): void
    {
        $events = $aggregate->extractEventsToDispatch();

        if (empty($events)) {
            $this->persist($aggregate);

            return;
        }

        $this->entityManager->wrapInTransaction(function () use ($aggregate, $events): void {
            $this->persist($aggregate);
            foreach ($events as $event) {
                $this->eventBus->handle($event);
            }
        });
    }

    private function persist(Aggregate $aggregate): void
    {
        $this->entityManager->persist($aggregate);
        $this->entityManager->flush();
    }
}
