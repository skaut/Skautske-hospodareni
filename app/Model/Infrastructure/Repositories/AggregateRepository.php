<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Repositories;

use App\Model\Common\Aggregate;
use App\Model\Common\Services\EventBus;
use Doctrine\ORM\EntityManager;

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
