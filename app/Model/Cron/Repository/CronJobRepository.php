<?php

declare(strict_types=1);

namespace App\Model\Cron\Repository;

use App\Model\Cron\CronJobResult;
use App\Model\Cron\Entity\CronJobState;
use App\Model\Infrastructure\Repository\AbstractRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;

/** @extends AbstractRepository<CronJobState> */
class CronJobRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function getEntityClass(): string
    {
        return CronJobState::class;
    }

    public function findByName(string $name): ?CronJobState
    {
        return $this->findOneBy(['name' => $name]);
    }

    /** @return array<string, CronJobState> */
    public function findAllIndexedByName(): array
    {
        $states = [];

        foreach ($this->findAll() as $state) {
            $states[$state->getName()] = $state;
        }

        return $states;
    }

    /** Plain SQL keeps the run recorded even when the job closed the entity manager. */
    public function recordStart(string $name, DateTimeImmutable $at): void
    {
        $this->entityManager->getConnection()->executeStatement(
            'INSERT INTO cron_job (name, paused, last_started_at) VALUES (?, 0, ?)
                ON DUPLICATE KEY UPDATE last_started_at = ?',
            [$name, $at, $at],
            [Types::STRING, Types::DATETIME_IMMUTABLE, Types::DATETIME_IMMUTABLE],
        );
    }

    public function recordFinish(string $name, CronJobResult $result, ?string $error, DateTimeImmutable $at): void
    {
        $this->entityManager->getConnection()->executeStatement(
            'UPDATE cron_job SET last_finished_at = ?, last_result = ?, last_error = ? WHERE name = ?',
            [$at, $result->value, $error, $name],
            [Types::DATETIME_IMMUTABLE, Types::STRING, Types::TEXT, Types::STRING],
        );
    }
}
