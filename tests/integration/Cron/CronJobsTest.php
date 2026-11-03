<?php

declare(strict_types=1);

namespace App\Model\Cron;

use App\Model\Cron\Entity\CronJobState;
use App\Model\Cron\Manager\CronJobManager;
use App\Model\Cron\ReadModel\Queries\CronJobsQuery;
use App\Model\Cron\ReadModel\QueryHandlers\CronJobsQueryHandler;
use App\Model\Cron\Repository\CronJobRepository;
use App\Model\Cron\Services\CronJobRegistry;
use Contributte\Scheduler\CallbackJob;
use Contributte\Scheduler\Scheduler;
use DateTimeImmutable;
use IntegrationTest;
use InvalidArgumentException;

final class CronJobsTest extends IntegrationTest
{
    private const JOB = CronJobRegistry::PAYMENT_PAIRING;

    private CronJobRepository $repository;

    private CronJobManager $manager;

    private CronJobsQueryHandler $queryHandler;

    /** @return string[] */
    public function getTestedAggregateRoots(): array
    {
        return [CronJobState::class];
    }

    protected function _before(): void
    {
        $this->tester->useConfigFiles(['config/doctrine.neon']);

        parent::_before();

        $scheduler = new Scheduler();
        $scheduler->add(new CallbackJob(CronInterval::EVERY_2_HOURS->value, static function (): void {
        }), self::JOB);
        $registry = new CronJobRegistry($scheduler);

        $this->repository = new CronJobRepository($this->entityManager);
        $this->manager = new CronJobManager($this->entityManager, $this->repository, $registry);
        $this->queryHandler = new CronJobsQueryHandler($registry, $this->repository);
    }

    public function testRunIsRecordedWithoutEntityManager(): void
    {
        $this->repository->recordStart(self::JOB, new DateTimeImmutable('2026-11-03 10:00:02'));
        $this->repository->recordFinish(self::JOB, CronJobResult::FAILED, 'FIO is down.', new DateTimeImmutable('2026-11-03 10:00:14'));
        $this->repository->recordStart(self::JOB, new DateTimeImmutable('2026-11-03 12:00:01'));

        self::assertSame(1, (int) $this->tester->grabNumRecords('cron_job'));

        // Also proves the plain SQL writes the same columns the mapping describes.
        $state = $this->repository->findByName(self::JOB);
        self::assertNotNull($state);
        self::assertSame('2026-11-03 12:00:01', $state->getLastStartedAt()?->format('Y-m-d H:i:s'));
        self::assertSame('2026-11-03 10:00:14', $state->getLastFinishedAt()?->format('Y-m-d H:i:s'));
        self::assertSame(CronJobResult::FAILED, $state->getLastResult());
        self::assertSame('FIO is down.', $state->getLastError());
        self::assertFalse($state->isPaused());
    }

    public function testPausedJobHasNoNextRun(): void
    {
        $this->manager->pause(self::JOB, 'František Maša');

        $job = ($this->queryHandler)(new CronJobsQuery(new DateTimeImmutable('2026-11-03 10:30')))[self::JOB];

        self::assertTrue($job->paused);
        self::assertNull($job->nextRunAt);
        self::assertNull($job->missedRunAt);
        self::assertSame('František Maša', $job->updatedByName);

        $this->manager->resume(self::JOB, 'František Maša');

        $job = ($this->queryHandler)(new CronJobsQuery(new DateTimeImmutable('2026-11-03 10:30')))[self::JOB];

        self::assertFalse($job->paused);
        self::assertSame('2026-11-03 12:00', $job->nextRunAt?->format('Y-m-d H:i'));
    }

    public function testChosenIntervalReplacesTheDefaultUntilTheDefaultIsChosenAgain(): void
    {
        $this->manager->changeSchedule(self::JOB, CronInterval::HOURLY, null);

        $job = ($this->queryHandler)(new CronJobsQuery(new DateTimeImmutable('2026-11-03 10:30')))[self::JOB];

        self::assertSame('každou hodinu', $job->getIntervalLabel());
        self::assertFalse($job->hasDefaultSchedule());
        self::assertSame('2026-11-03 11:00', $job->nextRunAt?->format('Y-m-d H:i'));

        $this->manager->changeSchedule(self::JOB, CronInterval::EVERY_2_HOURS, null);

        self::assertNull($this->repository->findByName(self::JOB)?->getSchedule());
    }

    public function testRunIsMissedWhenNothingStartedSinceItsTime(): void
    {
        $now = new DateTimeImmutable('2026-11-03 10:30');

        self::assertSame(
            '2026-11-03 10:00',
            ($this->queryHandler)(new CronJobsQuery($now))[self::JOB]->missedRunAt?->format('Y-m-d H:i'),
        );

        $this->repository->recordStart(self::JOB, new DateTimeImmutable('2026-11-03 10:00:03'));

        self::assertNull(($this->queryHandler)(new CronJobsQuery($now))[self::JOB]->missedRunAt);
    }

    public function testUnknownJobCannotBeChanged(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->manager->pause('unknownJob', null);
    }
}
