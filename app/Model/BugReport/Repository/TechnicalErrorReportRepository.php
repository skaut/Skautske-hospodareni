<?php

declare(strict_types=1);

namespace App\Model\BugReport\Repository;

use App\Model\BugReport\Entity\TechnicalErrorReport;
use App\Model\Infrastructure\Repository\AbstractRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

class TechnicalErrorReportRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function getEntityClass(): string
    {
        return TechnicalErrorReport::class;
    }

    public function createGridQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('report')
            ->andWhere('report.resolvedAt IS NULL');
    }

    public function findUnresolved(int $id): ?TechnicalErrorReport
    {
        return $this->findOneBy([
            'id' => $id,
            'resolvedAt' => null,
        ]);
    }
}
