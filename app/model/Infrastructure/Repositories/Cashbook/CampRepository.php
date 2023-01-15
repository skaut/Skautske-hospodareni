<?php

declare(strict_types=1);

namespace Model\Infrastructure\Repositories\Cashbook;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use Model\Cashbook\Camp;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\CashbookType;
use Model\Cashbook\CashbookNotFound;
use Model\Cashbook\Commands\Cashbook\CreateCashbook;
use Model\Cashbook\Repositories\ICampRepository;
use Model\Common\Services\CommandBus;
use Model\Common\Services\EventBus;
use Model\Event\SkautisCampId;
use Model\Infrastructure\Repositories\AggregateRepository;

final class CampRepository extends AggregateRepository implements ICampRepository
{
    public function __construct(EntityManager $entityManager, EventBus $eventBus, private CommandBus $commandBus)
    {
        parent::__construct($entityManager, $eventBus);
    }

    public function findBySkautisId(SkautisCampId $id): Camp
    {
        $builder = $this->getEntityManager()->createQueryBuilder();

        try {
            return $builder->select('c')
                ->from(Camp::class, 'c')
                ->where('c.id = :skautisId')
                ->setParameter('skautisId', $id->toInt())
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException) {
            $cashbook = new Camp($id, CashbookId::generate());
            $this->save($cashbook);

            $this->commandBus->handle(new CreateCashbook($cashbook->getCashbookId(), CashbookType::get(CashbookType::CAMP)));

            return $cashbook;
        }
    }

    public function findByCashbookId(CashbookId $cashbookId): Camp
    {
        $builder = $this->getEntityManager()->createQueryBuilder();

        try {
            return $builder->select('c')
                ->from(Camp::class, 'c')
                ->where('c.cashbookId = :cashbookId')
                ->setParameter('cashbookId', $cashbookId)
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException) {
            throw new CashbookNotFound();
        }
    }

    private function save(Camp $cashbook): void
    {
        $this->saveAndDispatchEvents($cashbook);
    }
}
