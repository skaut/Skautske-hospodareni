<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Repositories\Cashbook;

use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\Cashbook\CashbookType;
use App\Model\Cashbook\CashbookNotFound;
use App\Model\Cashbook\Commands\Cashbook\CreateCashbook;
use App\Model\Cashbook\Event;
use App\Model\Cashbook\Repositories\IEventRepository;
use App\Model\Common\Services\CommandBus;
use App\Model\Common\Services\EventBus;
use App\Model\Event\SkautisEventId;
use App\Model\Infrastructure\Repositories\AggregateRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;

final class EventRepository extends AggregateRepository implements IEventRepository
{
    public function __construct(EntityManager $entityManager, EventBus $eventBus, private CommandBus $commandBus)
    {
        parent::__construct($entityManager, $eventBus);
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
        } catch (NoResultException) {
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
        } catch (NoResultException) {
            throw new CashbookNotFound();
        }
    }

    private function save(Event $cashbook): void
    {
        $this->saveAndDispatchEvents($cashbook);
    }
}
