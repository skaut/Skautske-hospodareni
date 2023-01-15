<?php

declare(strict_types=1);

namespace Model\Infrastructure\Repositories\Cashbook;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\CashbookType;
use Model\Cashbook\CashbookNotFound;
use Model\Cashbook\Commands\Cashbook\CreateCashbook;
use Model\Cashbook\Education;
use Model\Cashbook\Repositories\IEducationRepository;
use Model\Common\Services\CommandBus;
use Model\Common\Services\EventBus;
use Model\Event\SkautisEducationId;
use Model\Infrastructure\Repositories\AggregateRepository;

final class EducationRepository extends AggregateRepository implements IEducationRepository
{
    public function __construct(EntityManager $entityManager, EventBus $eventBus, private CommandBus $commandBus)
    {
        parent::__construct($entityManager, $eventBus);
    }

    public function findBySkautisId(SkautisEducationId $id): Education
    {
        $builder = $this->getEntityManager()->createQueryBuilder();

        try {
            return $builder->select('e')
                ->from(Education::class, 'e')
                ->where('e.id = :skautisId')
                ->setParameter('skautisId', $id->toInt())
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException) {
            $cashbook = new Education($id, CashbookId::generate());
            $this->save($cashbook);

            $this->commandBus->handle(new CreateCashbook($cashbook->getCashbookId(), CashbookType::get(CashbookType::EDUCATION)));

            return $cashbook;
        }
    }

    public function findByCashbookId(CashbookId $cashbookId): Education
    {
        $builder = $this->getEntityManager()->createQueryBuilder();

        try {
            return $builder->select('c')
                ->from(Education::class, 'c')
                ->where('c.cashbookId = :cashbookId')
                ->setParameter('cashbookId', $cashbookId)
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException) {
            throw new CashbookNotFound();
        }
    }

    private function save(Education $cashbook): void
    {
        $this->saveAndDispatchEvents($cashbook);
    }
}
