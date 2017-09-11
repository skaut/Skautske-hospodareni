<?php


namespace Model\Infrastructure\Repositories;


use Doctrine\ORM\EntityManager;
use eGen\MessageBus\Bus\EventBus;
use Model\Common\AbstractAggregate;

abstract class AbstractRepository
{

    /** @var EntityManager */
    private $entityManager;

    /** @var EventBus */
    private $eventBus;


    public function __construct(EntityManager $entityManager, EventBus $eventBus)
    {
        $this->entityManager = $entityManager;
        $this->eventBus = $eventBus;
    }

    protected function getEntityManager(): EntityManager
    {
        return $this->entityManager;
    }

    protected function saveAndDispatchEvents(AbstractAggregate $aggregate): void
    {
        $events = $aggregate->extractEventsToDispatch();

        if(empty($events)) {
            $this->persist($aggregate);
            return;
        }

        $this->entityManager->transactional(function() use ($aggregate, $events) {
            $this->persist($aggregate);
            foreach($events as $event) {
                $this->eventBus->handle($event);
            }
        });
    }

    private function persist(AbstractAggregate $aggregate): void
    {
        $this->entityManager->persist($aggregate);
        $this->entityManager->flush();
    }

}
