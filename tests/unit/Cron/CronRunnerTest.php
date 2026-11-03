<?php

declare(strict_types=1);

namespace App\Model\Cron;

use App\Model\Cron\Entity\CronJobState;
use App\Model\Cron\Repository\CronJobRepository;
use App\Model\Cron\Services\CronJobRegistry;
use Codeception\Test\Unit;
use Contributte\Scheduler\CallbackJob;
use Contributte\Scheduler\Scheduler;
use DateTime;
use DateTimeImmutable;
use Mockery as m;
use Mockery\MockInterface;
use Nette\Utils\FileSystem;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

use function fclose;
use function flock;
use function fopen;
use function sys_get_temp_dir;
use function uniqid;

use const LOCK_EX;

final class CronRunnerTest extends Unit
{
    private string $lockDir;

    private Scheduler $scheduler;

    /** @var list<string> */
    private array $ran = [];

    protected function _before(): void
    {
        $this->lockDir = sys_get_temp_dir().'/'.uniqid('cron-runner-', true);
        $this->scheduler = new Scheduler();
        $this->ran = [];
    }

    protected function _after(): void
    {
        FileSystem::delete($this->lockDir);
    }

    public function testRunsOnlyDueJobsAndRecordsTheirRun(): void
    {
        $this->addJob('pairing', CronInterval::EVERY_2_HOURS->value);
        $this->addJob('other', '30 * * * *');
        $states = $this->states([]);
        $states->expects('recordStart')->with('pairing', m::type(DateTimeImmutable::class));
        $states->expects('recordFinish')->with('pairing', CronJobResult::SUCCEEDED, null, m::type(DateTimeImmutable::class));

        $results = $this->runner($states)->runDueJobs(new DateTime('2026-11-03 10:00:42'));

        $this->assertSame(['pairing' => CronJobResult::SUCCEEDED], $results);
        $this->assertSame(['pairing'], $this->ran);
    }

    public function testSkipsPausedJob(): void
    {
        $this->addJob('pairing', '* * * * *');
        $state = new CronJobState('pairing');
        $state->pause(new DateTimeImmutable(), null);

        $results = $this->runner($this->states(['pairing' => $state]))->runDueJobs(new DateTime());

        $this->assertSame([], $results);
        $this->assertSame([], $this->ran);
    }

    public function testIntervalChosenInAdministrationReplacesTheDefault(): void
    {
        $this->addJob('pairing', CronInterval::EVERY_2_HOURS->value);
        $state = new CronJobState('pairing');
        $state->changeSchedule(CronInterval::EVERY_4_HOURS, new DateTimeImmutable(), null);
        $states = $this->states(['pairing' => $state]);
        $states->shouldReceive('recordStart', 'recordFinish');
        $runner = $this->runner($states);

        // 14:00 is due by the default of every 2 hours, but not by the chosen every 4 hours.
        $this->assertSame([], $runner->runDueJobs(new DateTime('2026-11-03 14:00:05')));
        $this->assertSame(['pairing' => CronJobResult::SUCCEEDED], $runner->runDueJobs(new DateTime('2026-11-03 16:00:05')));
    }

    public function testFailedJobIsLoggedAndOtherJobsStillRun(): void
    {
        $exception = new RuntimeException('Database is gone.');
        $this->scheduler->add(new CallbackJob('* * * * *', static function () use ($exception): void {
            throw $exception;
        }), 'failing');
        $this->addJob('next', '* * * * *');
        $states = $this->states([]);
        $states->shouldReceive('recordStart');
        $states->expects('recordFinish')->with('failing', CronJobResult::FAILED, 'Database is gone.', m::type(DateTimeImmutable::class));
        $states->expects('recordFinish')->with('next', CronJobResult::SUCCEEDED, null, m::type(DateTimeImmutable::class));
        $logger = m::mock(LoggerInterface::class);
        $logger->expects('error')->with('Cron job "failing" failed: Database is gone.', ['exception' => $exception]);

        $results = $this->runner($states, $logger)->runDueJobs(new DateTime());

        $this->assertSame(['failing' => CronJobResult::FAILED, 'next' => CronJobResult::SUCCEEDED], $results);
        $this->assertSame(['next'], $this->ran);
    }

    public function testSkipsJobThatIsStillRunning(): void
    {
        $this->addJob('pairing', '* * * * *');
        FileSystem::createDir($this->lockDir);
        $runningLock = fopen($this->lockDir.'/pairing.lock', 'c');
        $this->assertNotFalse($runningLock);
        flock($runningLock, LOCK_EX);

        try {
            $results = $this->runner($this->states([]))->runDueJobs(new DateTime());
        } finally {
            fclose($runningLock);
        }

        $this->assertSame(['pairing' => CronJobResult::ALREADY_RUNNING], $results);
        $this->assertSame([], $this->ran);
    }

    private function addJob(string $name, string $schedule): void
    {
        $this->scheduler->add(new CallbackJob($schedule, function () use ($name): void {
            $this->ran[] = $name;
        }), $name);
    }

    /** @param array<string, CronJobState> $states */
    private function states(array $states): CronJobRepository&MockInterface
    {
        $repository = m::mock(CronJobRepository::class);
        $repository->allows('findAllIndexedByName')->andReturn($states);

        return $repository;
    }

    private function runner(CronJobRepository $states, ?LoggerInterface $logger = null): CronRunner
    {
        return new CronRunner(new CronJobRegistry($this->scheduler), $states, $this->lockDir, $logger ?? new NullLogger());
    }
}
