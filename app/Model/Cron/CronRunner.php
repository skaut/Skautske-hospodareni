<?php

declare(strict_types=1);

namespace App\Model\Cron;

use App\Model\Cron\Repository\CronJobRepository;
use App\Model\Cron\Services\CronJobRegistry;
use DateTime;
use DateTimeImmutable;
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
 * Runs due scheduler jobs for www/cron.php. Jobs paused in the administration are skipped and a schedule
 * chosen there replaces the default one. Unlike LockingScheduler it reports the outcome of each job,
 * so a failure reaches the error log, the administration and the response to the external caller.
 */
final class CronRunner
{
    public function __construct(
        private CronJobRegistry $registry,
        private CronJobRepository $states,
        private string $lockDir,
        private LoggerInterface $logger,
    ) {
    }

    /** @return array<string, CronJobResult> outcomes of the due jobs by job name */
    public function runDueJobs(DateTime $now): array
    {
        FileSystem::createDir($this->lockDir);
        $states = $this->states->findAllIndexedByName();
        $results = [];

        foreach ($this->registry->getJobs() as $name => $job) {
            $state = $states[$name] ?? null;

            if ($state?->isPaused() === true || ! $this->registry->getSchedule($name, $state)->isDue($now)) {
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

            $error = null;

            try {
                $this->states->recordStart($name, new DateTimeImmutable());
                $job->run();
                $results[$name] = CronJobResult::SUCCEEDED;
            } catch (Throwable $exception) {
                $this->logger->error(sprintf('Cron job "%s" failed: %s', $name, $exception->getMessage()), ['exception' => $exception]);
                $results[$name] = CronJobResult::FAILED;
                $error = $exception->getMessage();
            } finally {
                flock($lock, LOCK_UN);
                fclose($lock);
            }

            $this->states->recordFinish($name, $results[$name], $error, new DateTimeImmutable());
        }

        return $results;
    }
}
