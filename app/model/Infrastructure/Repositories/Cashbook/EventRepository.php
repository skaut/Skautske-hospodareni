<?php

declare(strict_types=1);

namespace Model\Infrastructure\Repositories\Cashbook;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use eGen\MessageBus\Bus\EventBus;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\CashbookType;
use Model\Cashbook\CashbookNotFound;
use Model\Cashbook\Commands\Cashbook\CreateCashbook;
use Model\Cashbook\Event;
use Model\Cashbook\Repositories\IEventRepository;
use Model\Common\Services\CommandBus;
use Model\Event\SkautisEventId;
use Model\Infrastructure\Repositories\AggregateRepository;

final class EventRepository extends AggregateRepository implements IEventRepository
{
    private CommandBus $commandBus;

    public function __construct(EntityManager $entityManager, EventBus $eventBus, CommandBus $commandBus)
    {
        parent::__construct($entityManager, $eventBus);
        $this->commandBus = $commandBus;
    }

    public function findBySkautisId(SkautisEventId $id): Event
    {
        $builder = $this->getEntityManager()->createQueryBuilder();

        try {
            return $builder->select('e')
                ->from(Event::class, 'e')
                ->where('e.id = :skautisId')
                ->setParameter('skautisId', $id->toInt())
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException $e) {
            $cashbook = new Event($id, CashbookId::generate());
            $this->save($cashbook);

            $this->commandBus->handle(new CreateCashbook($cashbook->getCashbookId(), CashbookType::get(CashbookType::EVENT)));

            return $cashbook;
        }
    }

    public function findByCashbookId(CashbookId $cashbookId): Event
    {
        $builder = $this->getEntityManager()->createQueryBuilder();

        try {
            return $builder->select('c')
                ->from(Event::class, 'c')
                ->where('c.cashbookId = :cashbookId')
                ->setParameter('cashbookId', $cashbookId)
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException $e) {
            throw new CashbookNotFound();
        }
    }

    private function save(Event $cashbook): void
    {
        $this->saveAndDispatchEvents($cashbook);
    }
}
