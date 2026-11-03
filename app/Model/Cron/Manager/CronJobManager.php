<?php

declare(strict_types=1);

namespace App\Model\Cron\Manager;

use App\Model\Cron\CronInterval;
use App\Model\Cron\Entity\CronJobState;
use App\Model\Cron\Repository\CronJobRepository;
use App\Model\Cron\Services\CronJobRegistry;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

use function sprintf;

class CronJobManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CronJobRepository $repository,
        private CronJobRegistry $registry,
    ) {
    }

    public function pause(string $name, ?string $editorName): void
    {
        $state = $this->getState($name);
        $state->pause(new DateTimeImmutable(), $editorName);
        $this->save($state);
    }

    public function resume(string $name, ?string $editorName): void
    {
        $state = $this->getState($name);
        $state->resume(new DateTimeImmutable(), $editorName);
        $this->save($state);
    }

    /**
     * Null or the default interval drops the override, so a later change of the default in scheduler.neon applies.
     */
    public function changeSchedule(string $name, ?CronInterval $interval, ?string $editorName): void
    {
        $state = $this->getState($name);
        $state->changeSchedule(
            $interval?->value === $this->registry->getDefaultSchedule($name) ? null : $interval,
            new DateTimeImmutable(),
            $editorName,
        );
        $this->save($state);
    }

    private function getState(string $name): CronJobState
    {
        if (! $this->registry->has($name)) {
            throw new InvalidArgumentException(sprintf('Unknown cron job "%s".', $name));
        }

        return $this->repository->findByName($name) ?? new CronJobState($name);
    }

    private function save(CronJobState $state): void
    {
        $this->entityManager->persist($state);
        $this->entityManager->flush();
    }
}
