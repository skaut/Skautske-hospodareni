<?php

declare(strict_types=1);

namespace App\Model\TaskQueue;

use App\Model\TaskQueue\Exception\TaskFailed;
use App\Model\TaskQueue\Exception\TaskLost;
use App\Model\TaskQueue\Exception\TaskOutOfTime;
use App\Model\TaskQueue\Exception\TaskRetryLater;
use App\Model\TaskQueue\Exception\TaskSkipped;
use App\Model\TaskQueue\Repository\TaskQueueRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

use function bin2hex;
use function count;
use function is_numeric;
use function microtime;
use function random_bytes;
use function sprintf;

/**
 * Runs queued tasks. The hosting allows a web request at most 30 seconds and the cron cannot use
 * the command line, so www/cron.php calls processDue() every minute and the queue stops taking new
 * tasks when its time budget is spent. Each task runs at most once, see {@see TaskItemState}.
 */
final class TaskQueueProcessor
{
    public const UNCERTAIN_MESSAGE = 'Zpracování se přerušilo uprostřed, a tak nevíme, jestli proběhlo.';

    private const UNEXPECTED_FAILURE_MESSAGE = 'Při zpracování došlo k neočekávané chybě.';
    private const ABANDONED_MESSAGE = 'Úloha opakovaně nedoběhla, zpracování se přerušilo dřív, než mohla proběhnout.';

    /** A task whose run died this many times is not returned to the queue again. */
    private const MAX_ABANDONED_ATTEMPTS = 3;
    private const RETENTION = '-30 days';

    /** @var array<string, TaskHandler> */
    private array $handlers = [];

    /**
     * @param TaskHandler[] $handlers
     * @param int           $budgetSeconds       how long after the start of the request new tasks may be taken
     * @param int           $leaseSeconds        after this time a task of a run that did not finish counts as abandoned;
     *                                           it must be longer than any request can run
     * @param int           $requestLimitSeconds how long the hosting lets a request run
     * @param int           $irreversibleSeconds the longest irreversible step of a task, e.g. one call to Gmail
     */
    public function __construct(
        private TaskQueueRepository $repository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        array $handlers,
        private int $budgetSeconds,
        private int $leaseSeconds,
        private int $requestLimitSeconds = 30,
        private int $irreversibleSeconds = 9,
    ) {
        foreach ($handlers as $handler) {
            $this->handlers[$handler->getType()] = $handler;
        }
    }

    /**
     * The cron job. Takes one task from each waiting batch in turn, so a small batch does not wait for a large one.
     *
     * @param float|null $deadline Unix time when no new task may start; by default the budget after the start of the request
     */
    public function processDue(?float $deadline = null): void
    {
        $deadline ??= $this->requestStartedAt() + $this->budgetSeconds;
        // A task taken just before the deadline may spend time before its irreversible step (e.g. refreshing
        // the token of Google), so the step itself may only begin while it can still end within the request.
        $irreversibleBefore = $deadline - $this->budgetSeconds + $this->requestLimitSeconds - $this->irreversibleSeconds;
        $now = new DateTimeImmutable();

        $this->repository->deleteFinishedJobsCreatedBefore($now->modify(self::RETENTION));
        $this->repository->deleteRunsStartedBefore($now->modify(self::RETENTION));

        $stats = new TaskQueueRunStats();
        $claimedBefore = $now->modify(sprintf('-%d seconds', $this->leaseSeconds));
        $stats->recovered = $this->failRepeatedlyAbandoned($claimedBefore, $now)
            + $this->repository->recoverStale($claimedBefore, $now, self::UNCERTAIN_MESSAGE);
        // Only runs that did something are kept; the row exists before the first task, so a run cut off
        // by the hosting stays visible without its finish time.
        $runId = $stats->recovered > 0 ? $this->repository->startRun($now) : null;
        $servedByJob = [];
        $stopReason = TaskQueueRunStopReason::DEADLINE;

        while (microtime(true) < $deadline) {
            if (! $this->entityManager->isOpen()) {
                $stopReason = TaskQueueRunStopReason::ERROR;

                break;
            }

            $candidates = $this->repository->findFirstPendingItemPerJob(new DateTimeImmutable());

            if ($candidates === []) {
                $stopReason = TaskQueueRunStopReason::EMPTY;

                break;
            }

            $runId ??= $this->repository->startRun(new DateTimeImmutable());
            $jobId = $this->leastServedJob($candidates, $servedByJob);
            $servedByJob[$jobId] = ($servedByJob[$jobId] ?? 0) + 1;

            try {
                $stats->record($this->processItem($candidates[$jobId], $irreversibleBefore));
            } catch (TaskOutOfTime) {
                // A task had no time left for its irreversible step, so the run takes no more.
                break;
            }
        }

        if ($runId !== null) {
            $this->repository->finishRun($runId, $stats, $stopReason, new DateTimeImmutable());
        }
    }

    /**
     * @return TaskOutcome|null null when another run took the task
     *
     * @throws TaskOutOfTime when no time was left for the task; it is back in the queue
     */
    private function processItem(int $itemId, float $irreversibleBefore): ?TaskOutcome
    {
        $task = $this->repository->claim($itemId, bin2hex(random_bytes(16)), new DateTimeImmutable());

        if ($task === null) {
            return null;
        }

        $handler = $this->handlers[$task->type] ?? null;

        if ($handler === null) {
            $this->logger->error(sprintf('Task queue has no handler for task type "%s" (task #%d).', $task->type, $task->itemId));
            $this->repository->finish($task, TaskItemState::FAILED, self::UNEXPECTED_FAILURE_MESSAGE, new DateTimeImmutable());

            return new TaskOutcome(TaskItemState::FAILED, self::UNEXPECTED_FAILURE_MESSAGE);
        }

        $context = new TaskContext($task, $this->repository, $this->entityManager, $irreversibleBefore);

        try {
            $handler->handle($context);

            if (! $context->isCompleted()) {
                $context->complete();
            }

            return new TaskOutcome(TaskItemState::DONE);
        } catch (TaskLost) {
            return null;
        } catch (TaskOutOfTime $exception) {
            $this->repository->returnUnstarted($task);

            throw $exception;
        } catch (TaskSkipped $exception) {
            return $this->endWithoutEffect($handler, $task, TaskItemState::SKIPPED, $exception->getMessage());
        } catch (TaskFailed $exception) {
            $outcome = $this->endWithoutEffect($handler, $task, TaskItemState::FAILED, $exception->getMessage());

            if ($exception->abortJob) {
                $this->failRestOfJob($handler, $task, $exception->getMessage());
            }

            return $outcome;
        } catch (TaskRetryLater $exception) {
            if ($task->attempts >= $exception->maxAttempts) {
                return $this->endWithoutEffect($handler, $task, TaskItemState::FAILED, $exception->getMessage());
            }

            $this->repository->connection()->transactional(function () use ($handler, $task, $exception): void {
                $availableAt = (new DateTimeImmutable())->modify(sprintf('+%d seconds', $exception->delaySeconds));

                if ($this->repository->postpone($task, $availableAt, $exception->getMessage())) {
                    $handler->release($task->payload);
                }

                if ($exception->wholeBatch) {
                    $this->repository->postponeWaitingOfJob($task->jobId, $availableAt, $exception->getMessage());
                }
            });

            return new TaskOutcome(TaskItemState::PENDING, $exception->getMessage());
        } catch (Throwable $exception) {
            $this->logger->error(
                sprintf('Task #%d of type "%s" failed: %s', $task->itemId, $task->type, $exception->getMessage()),
                ['exception' => $exception],
            );

            if ($context->isIrreversibleStarted()) {
                $this->repository->finish($task, TaskItemState::UNCERTAIN, self::UNCERTAIN_MESSAGE, new DateTimeImmutable());

                return new TaskOutcome(TaskItemState::UNCERTAIN, self::UNCERTAIN_MESSAGE);
            }

            return $this->endWithoutEffect($handler, $task, TaskItemState::FAILED, self::UNEXPECTED_FAILURE_MESSAGE);
        } finally {
            if ($this->entityManager->isOpen()) {
                $this->entityManager->clear();
            }
        }
    }

    private function endWithoutEffect(TaskHandler $handler, ClaimedTask $task, TaskItemState $state, string $message): TaskOutcome
    {
        $this->repository->connection()->transactional(function () use ($handler, $task, $state, $message): void {
            if ($this->repository->finish($task, $state, $message, new DateTimeImmutable())) {
                $handler->release($task->payload);
            }
        });

        return new TaskOutcome($state, $message);
    }

    private function failRepeatedlyAbandoned(DateTimeImmutable $claimedBefore, DateTimeImmutable $now): int
    {
        return $this->repository->connection()->transactional(function () use ($claimedBefore, $now): int {
            $failed = $this->repository->failRepeatedlyAbandoned($claimedBefore, self::MAX_ABANDONED_ATTEMPTS, $now, self::ABANDONED_MESSAGE);

            foreach ($failed as $task) {
                $this->logger->error(sprintf('Task of type "%s" was abandoned %d times and failed.', $task['type'], self::MAX_ABANDONED_ATTEMPTS));
                ($this->handlers[$task['type']] ?? null)?->release($task['payload']);
            }

            return count($failed);
        });
    }

    private function failRestOfJob(TaskHandler $handler, ClaimedTask $task, string $message): void
    {
        $this->repository->connection()->transactional(function () use ($handler, $task, $message): void {
            foreach ($this->repository->failPendingItemsOfJob($task->jobId, $message, new DateTimeImmutable()) as $payload) {
                $handler->release($payload);
            }
        });
    }

    /**
     * @param array<int, int> $candidates  first pending task by batch id
     * @param array<int, int> $servedByJob tasks taken from each batch in this run
     */
    private function leastServedJob(array $candidates, array $servedByJob): int
    {
        $chosen = null;

        foreach ($candidates as $jobId => $itemId) {
            if ($chosen === null || ($servedByJob[$jobId] ?? 0) < ($servedByJob[$chosen] ?? 0)) {
                $chosen = $jobId;
            }
        }

        return (int) $chosen;
    }

    /** The budget counts from the start of the request, so jobs that ran before in the same cron call use it up too. */
    private function requestStartedAt(): float
    {
        $startedAt = $_SERVER['REQUEST_TIME_FLOAT'] ?? null;

        return is_numeric($startedAt) ? (float) $startedAt : microtime(true);
    }
}
