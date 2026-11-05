<?php

declare(strict_types=1);

namespace App\Model\TaskQueue\ReadModel;

use App\Model\DTO\TaskQueue\TaskJobStatus;
use App\Model\DTO\TaskQueue\TaskProblem;
use App\Model\TaskQueue\TaskItemState;
use App\Model\TaskQueue\TaskScope;
use DateTimeImmutable;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;

use function array_column;
use function array_map;
use function array_values;

/**
 * Reads progress of batches. The panel of a page reads one scope, the administration all of them.
 */
final class TaskJobStatusReader
{
    /** A batch with no progress for this long most likely waits for a cron that stopped running. */
    private const STALLED_AFTER = '-10 minutes';

    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * Active batches and finished ones with a problem nobody hid yet, plus the followed ones.
     *
     * @param TaskScope|null $scope    null reads batches of all scopes
     * @param int[]          $followed batches to return even when finished; never of another scope
     *
     * @return list<TaskJobStatus> oldest first
     */
    public function read(?TaskScope $scope, DateTimeImmutable $now, array $followed = []): array
    {
        $connection = $this->entityManager->getConnection();

        // The scope is part of every condition, so a followed id can never show a batch of another scope.
        $rows = $connection->fetchAllAssociative(
            'SELECT j.id, j.type, j.scope_type, j.scope_id, j.label, j.created_at, j.created_by_name,
                    COUNT(*) AS total,
                    SUM(i.state = :done) AS done,
                    SUM(i.state IN (:active)) AS active,
                    SUM(i.state = :skipped) AS skipped,
                    SUM(i.state = :failed) AS failed,
                    SUM(i.state = :uncertain) AS uncertain,
                    GREATEST(j.created_at, COALESCE(MAX(i.claimed_at), j.created_at), COALESCE(MAX(i.finished_at), j.created_at)) AS last_activity
                FROM task_queue_job j
                JOIN task_queue_item i ON i.job_id = j.id
                WHERE (:allScopes = 1 OR (j.scope_type = :scopeType AND j.scope_id = :scopeId)) AND j.acknowledged_at IS NULL
                GROUP BY j.id
                HAVING active > 0 OR failed > 0 OR uncertain > 0 OR j.id IN (:followed)
                ORDER BY j.id',
            [
                'done' => TaskItemState::DONE->value,
                'active' => array_map(static fn (TaskItemState $state): string => $state->value, TaskItemState::active()),
                'skipped' => TaskItemState::SKIPPED->value,
                'failed' => TaskItemState::FAILED->value,
                'uncertain' => TaskItemState::UNCERTAIN->value,
                'allScopes' => $scope === null ? 1 : 0,
                'scopeType' => $scope->type ?? '',
                'scopeId' => $scope->id ?? 0,
                // IN () is invalid SQL, 0 is never a batch id.
                'followed' => $followed === [] ? [0] : array_values($followed),
            ],
            [
                'active' => ArrayParameterType::STRING,
                'allScopes' => Types::INTEGER,
                'scopeId' => Types::INTEGER,
                'followed' => ArrayParameterType::INTEGER,
            ],
        );

        if ($rows === []) {
            return [];
        }

        $problems = $this->findProblems(array_map('intval', array_column($rows, 'id')));
        $stalledBefore = $now->modify(self::STALLED_AFTER);

        return array_map(
            static function (array $row) use ($problems, $stalledBefore): TaskJobStatus {
                $active = (int) $row['active'];

                return new TaskJobStatus(
                    (int) $row['id'],
                    (string) $row['type'],
                    (string) $row['scope_type'],
                    (int) $row['scope_id'],
                    (string) $row['label'],
                    new DateTimeImmutable((string) $row['created_at']),
                    $row['created_by_name'] === null ? null : (string) $row['created_by_name'],
                    (int) $row['total'],
                    (int) $row['done'],
                    $active,
                    (int) $row['skipped'],
                    (int) $row['failed'],
                    (int) $row['uncertain'],
                    $active > 0 && new DateTimeImmutable((string) $row['last_activity']) < $stalledBefore,
                    $problems[(int) $row['id']] ?? [],
                );
            },
            $rows,
        );
    }

    /**
     * @param list<int> $jobIds
     *
     * @return array<int, list<TaskProblem>> by batch id
     */
    private function findProblems(array $jobIds): array
    {
        $rows = $this->entityManager->getConnection()->fetchAllAssociative(
            'SELECT job_id, label, state, message FROM task_queue_item
                WHERE job_id IN (:jobIds) AND state IN (:states)
                ORDER BY id',
            [
                'jobIds' => $jobIds,
                'states' => [TaskItemState::SKIPPED->value, TaskItemState::FAILED->value, TaskItemState::UNCERTAIN->value],
            ],
            [
                'jobIds' => ArrayParameterType::INTEGER,
                'states' => ArrayParameterType::STRING,
            ],
        );

        $problems = [];

        foreach ($rows as $row) {
            $problems[(int) $row['job_id']][] = new TaskProblem(
                (string) $row['label'],
                TaskItemState::from((string) $row['state']),
                $row['message'] === null ? null : (string) $row['message'],
            );
        }

        return $problems;
    }
}
