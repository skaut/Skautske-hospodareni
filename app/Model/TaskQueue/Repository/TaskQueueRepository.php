<?php

declare(strict_types=1);

namespace App\Model\TaskQueue\Repository;

use App\Model\TaskQueue\ClaimedTask;
use App\Model\TaskQueue\NewTask;
use App\Model\TaskQueue\TaskItemState;
use App\Model\TaskQueue\TaskQueueRunStats;
use App\Model\TaskQueue\TaskQueueRunStopReason;
use App\Model\TaskQueue\TaskScope;
use DateTimeImmutable;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Nette\Utils\Json;
use Nette\Utils\Strings;

use function array_column;
use function array_map;
use function is_array;

/**
 * Every state change is one conditional UPDATE, so only the run that changed the row owns the task.
 * Plain SQL keeps the queue working even when a failed task closed the entity manager.
 */
final class TaskQueueRepository
{
    private const LABEL_MAX_LENGTH = 255;

    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function createJob(string $type, TaskScope $scope, string $label, ?string $createdByName, DateTimeImmutable $now): int
    {
        $this->connection()->insert(
            'task_queue_job',
            [
                'type' => $type,
                'scope_type' => $scope->type,
                'scope_id' => $scope->id,
                'label' => Strings::truncate($label, self::LABEL_MAX_LENGTH),
                'created_at' => $now,
                'created_by_name' => $createdByName,
            ],
            [
                'created_at' => Types::DATETIME_IMMUTABLE,
            ],
        );

        return (int) $this->connection()->lastInsertId();
    }

    /** @return bool false when the same task (dedup key) already waits in the queue */
    public function addItem(int $jobId, NewTask $task, DateTimeImmutable $now): bool
    {
        return (int) $this->connection()->executeStatement(
            'INSERT INTO task_queue_item (job_id, state, payload, label, dedup_key, attempts, available_at)
                VALUES (?, ?, ?, ?, ?, 0, ?)
                ON DUPLICATE KEY UPDATE id = id',
            [
                $jobId,
                TaskItemState::PENDING->value,
                Json::encode($task->payload),
                Strings::truncate($task->label, self::LABEL_MAX_LENGTH),
                $task->dedupKey,
                $now,
            ],
            [Types::INTEGER, Types::STRING, Types::STRING, Types::STRING, Types::STRING, Types::DATETIME_IMMUTABLE],
        ) === 1;
    }

    /**
     * Adds a task to a batch only when the batch still exists, e.g. when it was created earlier in
     * this request inside a transaction that was rolled back since.
     *
     * @return bool false when the batch is gone or the same task already waits in the queue
     */
    public function addItemToExistingJob(int $jobId, NewTask $task, DateTimeImmutable $now): bool
    {
        return (int) $this->connection()->executeStatement(
            'INSERT INTO task_queue_item (job_id, state, payload, label, dedup_key, attempts, available_at)
                SELECT id, ?, ?, ?, ?, 0, ? FROM task_queue_job WHERE id = ?
                ON DUPLICATE KEY UPDATE task_queue_item.id = task_queue_item.id',
            [
                TaskItemState::PENDING->value,
                Json::encode($task->payload),
                Strings::truncate($task->label, self::LABEL_MAX_LENGTH),
                $task->dedupKey,
                $now,
                $jobId,
            ],
            [Types::STRING, Types::STRING, Types::STRING, Types::STRING, Types::DATETIME_IMMUTABLE, Types::INTEGER],
        ) === 1;
    }

    public function deleteJob(int $jobId): void
    {
        $this->connection()->executeStatement('DELETE FROM task_queue_job WHERE id = ?', [$jobId]);
    }

    public function hasActiveItem(string $dedupKey): bool
    {
        return $this->connection()->fetchOne('SELECT 1 FROM task_queue_item WHERE dedup_key = ?', [$dedupKey]) !== false;
    }

    /** @return array<int, int> the first pending task of each batch, by batch id */
    public function findFirstPendingItemPerJob(DateTimeImmutable $now): array
    {
        $rows = $this->connection()->fetchAllAssociative(
            'SELECT job_id, MIN(id) AS id FROM task_queue_item
                WHERE state = ? AND available_at <= ?
                GROUP BY job_id
                ORDER BY job_id',
            [TaskItemState::PENDING->value, $now],
            [Types::STRING, Types::DATETIME_IMMUTABLE],
        );

        return array_map('intval', array_column($rows, 'id', 'job_id'));
    }

    public function findFirstPendingItemOfJob(int $jobId, DateTimeImmutable $now): ?int
    {
        $id = $this->connection()->fetchOne(
            'SELECT MIN(id) FROM task_queue_item WHERE job_id = ? AND state = ? AND available_at <= ?',
            [$jobId, TaskItemState::PENDING->value, $now],
            [Types::INTEGER, Types::STRING, Types::DATETIME_IMMUTABLE],
        );

        return $id === null || $id === false ? null : (int) $id;
    }

    /** @return ClaimedTask|null null when another run took the task first */
    public function claim(int $itemId, string $token, DateTimeImmutable $now): ?ClaimedTask
    {
        $claimed = (int) $this->connection()->executeStatement(
            'UPDATE task_queue_item SET state = ?, claim_token = ?, claimed_at = ?, attempts = attempts + 1
                WHERE id = ? AND state = ?',
            [TaskItemState::CLAIMED->value, $token, $now, $itemId, TaskItemState::PENDING->value],
            [Types::STRING, Types::STRING, Types::DATETIME_IMMUTABLE, Types::INTEGER, Types::STRING],
        );

        if ($claimed !== 1) {
            return null;
        }

        $row = $this->connection()->fetchAssociative(
            'SELECT i.id, i.job_id, i.payload, i.attempts, j.type, j.created_by_name
                FROM task_queue_item i
                JOIN task_queue_job j ON j.id = i.job_id
                WHERE i.id = ?',
            [$itemId],
        );

        if ($row === false) {
            return null;
        }

        $payload = Json::decode((string) $row['payload'], forceArrays: true);

        return new ClaimedTask(
            (int) $row['id'],
            (int) $row['job_id'],
            (string) $row['type'],
            is_array($payload) ? $payload : [],
            $row['created_by_name'] === null ? null : (string) $row['created_by_name'],
            (int) $row['attempts'],
            $token,
        );
    }

    public function markRunning(ClaimedTask $task, DateTimeImmutable $now): bool
    {
        return (int) $this->connection()->executeStatement(
            'UPDATE task_queue_item SET state = ?, claimed_at = ? WHERE id = ? AND claim_token = ? AND state = ?',
            [TaskItemState::RUNNING->value, $now, $task->itemId, $task->token, TaskItemState::CLAIMED->value],
            [Types::STRING, Types::DATETIME_IMMUTABLE, Types::INTEGER, Types::STRING, Types::STRING],
        ) === 1;
    }

    /** A task marked uncertain after a stall still becomes done when its run finally succeeds. */
    public function markDone(ClaimedTask $task, DateTimeImmutable $now): bool
    {
        return (int) $this->connection()->executeStatement(
            'UPDATE task_queue_item SET state = ?, finished_at = ?, dedup_key = NULL, message = NULL
                WHERE id = ? AND claim_token = ? AND state IN (?)',
            [
                TaskItemState::DONE->value,
                $now,
                $task->itemId,
                $task->token,
                [TaskItemState::CLAIMED->value, TaskItemState::RUNNING->value, TaskItemState::UNCERTAIN->value],
            ],
            [Types::STRING, Types::DATETIME_IMMUTABLE, Types::INTEGER, Types::STRING, ArrayParameterType::STRING],
        ) === 1;
    }

    public function finish(ClaimedTask $task, TaskItemState $state, string $message, DateTimeImmutable $now): bool
    {
        return (int) $this->connection()->executeStatement(
            'UPDATE task_queue_item SET state = ?, finished_at = ?, dedup_key = NULL, message = ?
                WHERE id = ? AND claim_token = ? AND state IN (?)',
            [
                $state->value,
                $now,
                $message,
                $task->itemId,
                $task->token,
                [TaskItemState::CLAIMED->value, TaskItemState::RUNNING->value],
            ],
            [Types::STRING, Types::DATETIME_IMMUTABLE, Types::TEXT, Types::INTEGER, Types::STRING, ArrayParameterType::STRING],
        ) === 1;
    }

    public function postpone(ClaimedTask $task, DateTimeImmutable $availableAt, string $message): bool
    {
        return (int) $this->connection()->executeStatement(
            'UPDATE task_queue_item SET state = ?, claim_token = NULL, claimed_at = NULL, available_at = ?, message = ?
                WHERE id = ? AND claim_token = ? AND state IN (?)',
            [
                TaskItemState::PENDING->value,
                $availableAt,
                $message,
                $task->itemId,
                $task->token,
                [TaskItemState::CLAIMED->value, TaskItemState::RUNNING->value],
            ],
            [Types::STRING, Types::DATETIME_IMMUTABLE, Types::TEXT, Types::INTEGER, Types::STRING, ArrayParameterType::STRING],
        ) === 1;
    }

    /**
     * Fails the tasks of a batch that still wait. Call it inside a transaction.
     *
     * @return list<array<string, mixed>> payloads of the failed tasks
     */
    public function failPendingItemsOfJob(int $jobId, string $message, DateTimeImmutable $now): array
    {
        $rows = $this->connection()->fetchAllAssociative(
            'SELECT id, payload FROM task_queue_item WHERE job_id = ? AND state = ? FOR UPDATE',
            [$jobId, TaskItemState::PENDING->value],
        );

        if ($rows === []) {
            return [];
        }

        $this->connection()->executeStatement(
            'UPDATE task_queue_item SET state = ?, finished_at = ?, dedup_key = NULL, message = ?
                WHERE id IN (?) AND state = ?',
            [TaskItemState::FAILED->value, $now, $message, array_map('intval', array_column($rows, 'id')), TaskItemState::PENDING->value],
            [Types::STRING, Types::DATETIME_IMMUTABLE, Types::TEXT, ArrayParameterType::INTEGER, Types::STRING],
        );

        $payloads = [];

        foreach ($rows as $row) {
            $payload = Json::decode((string) $row['payload'], forceArrays: true);
            $payloads[] = is_array($payload) ? $payload : [];
        }

        return $payloads;
    }

    /**
     * A run that died leaves its task CLAIMED or RUNNING. A claimed task did nothing yet, so it waits
     * again. A running one may have had its effect, so it becomes UNCERTAIN and never runs again.
     *
     * @return int number of recovered tasks
     */
    public function recoverStale(DateTimeImmutable $claimedBefore, DateTimeImmutable $now, string $uncertainMessage): int
    {
        $returned = (int) $this->connection()->executeStatement(
            'UPDATE task_queue_item SET state = ?, claim_token = NULL, claimed_at = NULL WHERE state = ? AND claimed_at < ?',
            [TaskItemState::PENDING->value, TaskItemState::CLAIMED->value, $claimedBefore],
            [Types::STRING, Types::STRING, Types::DATETIME_IMMUTABLE],
        );

        // The claim token stays, so a run that was only stalled may still record its success.
        $markedUncertain = (int) $this->connection()->executeStatement(
            'UPDATE task_queue_item SET state = ?, finished_at = ?, dedup_key = NULL, message = ? WHERE state = ? AND claimed_at < ?',
            [TaskItemState::UNCERTAIN->value, $now, $uncertainMessage, TaskItemState::RUNNING->value, $claimedBefore],
            [Types::STRING, Types::DATETIME_IMMUTABLE, Types::TEXT, Types::STRING, Types::DATETIME_IMMUTABLE],
        );

        return $returned + $markedUncertain;
    }

    public function startRun(DateTimeImmutable $now): int
    {
        $this->connection()->insert('task_queue_run', ['started_at' => $now], ['started_at' => Types::DATETIME_IMMUTABLE]);

        return (int) $this->connection()->lastInsertId();
    }

    public function finishRun(int $runId, TaskQueueRunStats $stats, TaskQueueRunStopReason $stopReason, DateTimeImmutable $now): void
    {
        $this->connection()->executeStatement(
            'UPDATE task_queue_run
                SET finished_at = ?, taken = ?, done = ?, skipped = ?, failed = ?, uncertain = ?, postponed = ?, recovered = ?,
                    stop_reason = ?, pending_after = (SELECT COUNT(*) FROM task_queue_item WHERE state = ?)
                WHERE id = ?',
            [
                $now,
                $stats->taken,
                $stats->done,
                $stats->skipped,
                $stats->failed,
                $stats->uncertain,
                $stats->postponed,
                $stats->recovered,
                $stopReason->value,
                TaskItemState::PENDING->value,
                $runId,
            ],
            [Types::DATETIME_IMMUTABLE],
        );
    }

    public function deleteRunsStartedBefore(DateTimeImmutable $before): void
    {
        $this->connection()->executeStatement(
            'DELETE FROM task_queue_run WHERE started_at < ?',
            [$before],
            [Types::DATETIME_IMMUTABLE],
        );
    }

    /** Batches without an active task are kept for a while as a history, then deleted together with their tasks. */
    public function deleteFinishedJobsCreatedBefore(DateTimeImmutable $before): void
    {
        $this->connection()->executeStatement(
            'DELETE FROM task_queue_job
                WHERE created_at < ?
                AND NOT EXISTS (SELECT 1 FROM task_queue_item i WHERE i.job_id = task_queue_job.id AND i.state IN (?))',
            [$before, $this->activeStates()],
            [Types::DATETIME_IMMUTABLE, ArrayParameterType::STRING],
        );
    }

    /** Hides a finished batch from the overview. Only a batch of the given scope can be hidden. */
    public function acknowledge(int $jobId, TaskScope $scope, ?string $name, DateTimeImmutable $now): bool
    {
        return (int) $this->connection()->executeStatement(
            'UPDATE task_queue_job SET acknowledged_at = ?, acknowledged_by_name = ?
                WHERE id = ? AND scope_type = ? AND scope_id = ? AND acknowledged_at IS NULL
                AND NOT EXISTS (SELECT 1 FROM task_queue_item i WHERE i.job_id = task_queue_job.id AND i.state IN (?))',
            [$now, $name, $jobId, $scope->type, $scope->id, $this->activeStates()],
            [Types::DATETIME_IMMUTABLE, Types::STRING, Types::INTEGER, Types::STRING, Types::INTEGER, ArrayParameterType::STRING],
        ) === 1;
    }

    public function connection(): Connection
    {
        return $this->entityManager->getConnection();
    }

    /** @return list<string> */
    private function activeStates(): array
    {
        return array_map(static fn (TaskItemState $state): string => $state->value, TaskItemState::active());
    }
}
