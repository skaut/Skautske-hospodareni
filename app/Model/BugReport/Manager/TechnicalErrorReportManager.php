<?php

declare(strict_types=1);

namespace App\Model\BugReport\Manager;

use App\Model\BugReport\Entity\TechnicalErrorReport;
use App\Model\Infrastructure\Manager\AbstractManager;
use Doctrine\ORM\EntityManagerInterface;

class TechnicalErrorReportManager extends AbstractManager
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function getEntityClass(): string
    {
        return TechnicalErrorReport::class;
    }

    public function create(TechnicalErrorReport $report): TechnicalErrorReport
    {
        return $this->em->wrapInTransaction(function () use ($report): TechnicalErrorReport {
            $this->em->persist($report);
            $this->em->flush();

            return $report;
        });
    }

    public function saveNotificationState(TechnicalErrorReport $report): void
    {
        $this->em->wrapInTransaction(function () use ($report): void {
            $this->em->persist($report);
            $this->em->flush();
        });
    }

    public function resolve(TechnicalErrorReport $report, ?string $message = null): void
    {
        $this->em->wrapInTransaction(function () use ($report, $message): void {
            $report->resolve($message);
            $this->em->persist($report);
            $this->em->flush();
        });
    }
}
