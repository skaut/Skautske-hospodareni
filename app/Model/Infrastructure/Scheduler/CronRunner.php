<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Scheduler;

use Contributte\Scheduler\IScheduler;
use DateTime;
use Nette\Utils\FileSystem;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

use function fclose;
use function flock;
use function fopen;
use function sprintf;

use const LOCK_EX;
use const LOCK_NB;
use const LOCK_UN;

/**
 * Runs due scheduler jobs for www/cron.php. Unlike LockingScheduler it reports the outcome of each job,
 * so a failure reaches the error log and the response to the external caller.
 */
final class CronRunner
{
    public function __construct(private IScheduler $scheduler, private string $lockDir, private LoggerInterface $logger)
    {
    }

    /** @return array<int|string, CronJobResult> outcomes of the due jobs by job name */
    public function runDueJobs(DateTime $now): array
    {
        FileSystem::createDir($this->lockDir);
        $results = [];

        foreach ($this->scheduler->getAll() as $name => $job) {
            if (! $job->isDue($now)) {
                continue;
            }

            $lock = fopen(sprintf('%s/%s.lock', $this->lockDir, $name), 'c');

            if ($lock === false) {
                throw new RuntimeException(sprintf('Cannot open the lock of cron job "%s".', $name));
            }

            if (! flock($lock, LOCK_EX | LOCK_NB)) {
                fclose($lock);
                $results[$name] = CronJobResult::ALREADY_RUNNING;

                continue;
            }

            try {
                $job->run();
                $results[$name] = CronJobResult::SUCCEEDED;
            } catch (Throwable $exception) {
                $this->logger->error(sprintf('Cron job "%s" failed: %s', $name, $exception->getMessage()), ['exception' => $exception]);
                $results[$name] = CronJobResult::FAILED;
            } finally {
                flock($lock, LOCK_UN);
                fclose($lock);
            }
        }

        return $results;
    }
}
