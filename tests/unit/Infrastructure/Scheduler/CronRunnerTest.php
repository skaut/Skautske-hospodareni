<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Scheduler;

use Codeception\Test\Unit;
use Contributte\Scheduler\CallbackJob;
use Contributte\Scheduler\Scheduler;
use DateTime;
use Mockery as m;
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

    protected function _before(): void
    {
        $this->lockDir = sys_get_temp_dir().'/'.uniqid('cron-runner-', true);
    }

    protected function _after(): void
    {
        FileSystem::delete($this->lockDir);
    }

    public function testRunsOnlyDueJobs(): void
    {
        $ran = [];
        $scheduler = new Scheduler();
        $scheduler->add(new CallbackJob('0 */2 * * *', static function () use (&$ran): void {
            $ran[] = 'pairing';
        }), 'pairing');
        $scheduler->add(new CallbackJob('30 * * * *', static function () use (&$ran): void {
            $ran[] = 'other';
        }), 'other');

        $results = (new CronRunner($scheduler, $this->lockDir, new NullLogger()))->runDueJobs(new DateTime('2026-11-03 10:00:42'));

        $this->assertSame(['pairing' => CronJobResult::SUCCEEDED], $results);
        $this->assertSame(['pairing'], $ran);
    }

    public function testFailedJobIsLoggedAndOtherJobsStillRun(): void
    {
        $exception = new RuntimeException('Database is gone.');
        $ran = false;
        $scheduler = new Scheduler();
        $scheduler->add(new CallbackJob('* * * * *', static function () use ($exception): void {
            throw $exception;
        }), 'failing');
        $scheduler->add(new CallbackJob('* * * * *', static function () use (&$ran): void {
            $ran = true;
        }), 'next');
        $logger = m::mock(LoggerInterface::class);
        $logger->expects('error')->with('Cron job "failing" failed: Database is gone.', ['exception' => $exception]);

        $results = (new CronRunner($scheduler, $this->lockDir, $logger))->runDueJobs(new DateTime());

        $this->assertSame(['failing' => CronJobResult::FAILED, 'next' => CronJobResult::SUCCEEDED], $results);
        $this->assertTrue($ran);
    }

    public function testSkipsJobThatIsStillRunning(): void
    {
        $scheduler = new Scheduler();
        $scheduler->add(new CallbackJob('* * * * *', static function (): void {
            throw new RuntimeException('The job must not run twice at once.');
        }), 'pairing');
        FileSystem::createDir($this->lockDir);
        $runningLock = fopen($this->lockDir.'/pairing.lock', 'c');
        $this->assertNotFalse($runningLock);
        flock($runningLock, LOCK_EX);

        try {
            $results = (new CronRunner($scheduler, $this->lockDir, new NullLogger()))->runDueJobs(new DateTime());
        } finally {
            fclose($runningLock);
        }

        $this->assertSame(['pairing' => CronJobResult::ALREADY_RUNNING], $results);
    }
}
