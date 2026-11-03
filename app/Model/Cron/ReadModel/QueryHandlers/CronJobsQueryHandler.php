<?php

declare(strict_types=1);

namespace App\Model\Cron\ReadModel\QueryHandlers;

use App\Model\Cron\Entity\CronJobState;
use App\Model\Cron\ReadModel\Queries\CronJobsQuery;
use App\Model\Cron\Repository\CronJobRepository;
use App\Model\Cron\Services\CronJobRegistry;
use App\Model\DTO\Cron\CronJob;
use Cron\CronExpression;
use DateTimeImmutable;

final class CronJobsQueryHandler
{
    public function __construct(private CronJobRegistry $registry, private CronJobRepository $states)
    {
    }

    /** @return array<string, CronJob> by job name, in the order of scheduler.neon */
    public function __invoke(CronJobsQuery $query): array
    {
        $states = $this->states->findAllIndexedByName();
        $jobs = [];

        foreach ($this->registry->getJobs() as $name => $job) {
            $state = $states[$name] ?? null;
            $schedule = $this->registry->getSchedule($name, $state);
            $paused = $state?->isPaused() ?? false;

            $jobs[$name] = new CronJob(
                $name,
                $this->registry->getTitle($name),
                $this->registry->getDescription($name),
                (string) $schedule->getExpression(),
                $this->registry->getDefaultSchedule($name),
                $paused,
                $paused ? null : DateTimeImmutable::createFromMutable($schedule->getNextRunDate($query->getNow())),
                $paused ? null : $this->findMissedRun($schedule, $state, $query->getNow()),
                $state?->getLastStartedAt(),
                $state?->getLastFinishedAt(),
                $state?->getLastResult(),
                $state?->getLastError(),
                $state?->getUpdatedAt(),
                $state?->getUpdatedByName(),
            );
        }

        return $jobs;
    }

    /** A run counts as missed when nothing started since its time and the job was not changed after it. */
    private function findMissedRun(CronExpression $schedule, ?CronJobState $state, DateTimeImmutable $now): ?DateTimeImmutable
    {
        $plannedAt = DateTimeImmutable::createFromMutable($schedule->getPreviousRunDate($now));

        foreach ([$state?->getLastStartedAt(), $state?->getUpdatedAt()] as $activity) {
            if ($activity !== null && $activity >= $plannedAt) {
                return null;
            }
        }

        return $plannedAt;
    }
}
