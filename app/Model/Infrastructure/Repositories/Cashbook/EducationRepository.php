<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Repositories\Cashbook;

use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\Cashbook\CashbookType;
use App\Model\Cashbook\CashbookNotFound;
use App\Model\Cashbook\Commands\Cashbook\CreateCashbook;
use App\Model\Cashbook\Education;
use App\Model\Cashbook\Repositories\IEducationRepository;
use App\Model\Common\Services\CommandBus;
use App\Model\Common\Services\EventBus;
use App\Model\Event\SkautisEducationId;
use App\Model\Infrastructure\Repositories\AggregateRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;

final class EducationRepository extends AggregateRepository implements IEducationRepository
{
    public function __construct(EntityManager $entityManager, EventBus $eventBus, private CommandBus $commandBus)
    {
        parent::__construct($entityManager, $eventBus);
    }

    public function findBySkautisIdAndYear(SkautisEducationId $id, int $year): Education
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
            $cashbook = new Education($id, $year, CashbookId::generate());
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
