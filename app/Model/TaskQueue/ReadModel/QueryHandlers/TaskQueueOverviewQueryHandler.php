<?php

declare(strict_types=1);

namespace App\Model\TaskQueue\ReadModel\QueryHandlers;

use App\Model\DTO\TaskQueue\TaskJobStatus;
use App\Model\DTO\TaskQueue\TaskQueueOverview;
use App\Model\DTO\TaskQueue\TaskQueueRunRow;
use App\Model\DTO\TaskQueue\TaskQueueRunTotals;
use App\Model\DTO\TaskQueue\TaskTypeSummary;
use App\Model\TaskQueue\ReadModel\Queries\TaskQueueOverviewQuery;
use App\Model\TaskQueue\ReadModel\TaskJobStatusReader;
use App\Model\TaskQueue\TaskHandler;
use App\Model\TaskQueue\TaskItemState;
use App\Model\TaskQueue\TaskQueueRunStopReason;
use App\Model\TaskQueue\TaskScope;
use DateTimeImmutable;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;

use function array_filter;
use function array_map;
use function array_unique;
use function array_values;
use function ksort;
use function round;
use function sprintf;

final class TaskQueueOverviewQueryHandler
{
    private const RECENT_RUNS = 25;
    private const LAST_DAY = '-24 hours';

    /** @var array<string, string> label of each task type */
    private array $typeLabels = [];

    /** @param TaskHandler[] $handlers */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TaskJobStatusReader $reader,
        array $handlers,
    ) {
        foreach ($handlers as $handler) {
            $this->typeLabels[$handler->getType()] = $handler->getLabel();
        }
    }

    public function __invoke(TaskQueueOverviewQuery $query): TaskQueueOverview
    {
        $since = $query->getNow()->modify(self::LAST_DAY);
        $jobs = $this->reader->read(null, $query->getNow());

        return new TaskQueueOverview(
            $this->summarizeTypes($since),
            $jobs,
            $this->scopeLabels($jobs),
            $this->recentRuns(),
            $this->lastDayTotals($since),
        );
    }

    /** @return list<TaskTypeSummary> every known type, also without any task */
    private function summarizeTypes(DateTimeImmutable $since): array
    {
        $rows = $this->entityManager->getConnection()->fetchAllAssociative(
            'SELECT j.type,
                    SUM(i.state = :pending) AS pending,
                    SUM(i.state IN (:running)) AS running,
                    SUM(i.state = :done AND i.finished_at >= :since) AS done,
                    SUM(i.state = :skipped AND i.finished_at >= :since) AS skipped,
                    SUM(i.state = :failed AND i.finished_at >= :since) AS failed,
                    SUM(i.state = :uncertain AND i.finished_at >= :since) AS uncertain
                FROM task_queue_item i
                JOIN task_queue_job j ON j.id = i.job_id
                GROUP BY j.type',
            [
                'pending' => TaskItemState::PENDING->value,
                'running' => [TaskItemState::CLAIMED->value, TaskItemState::RUNNING->value],
                'done' => TaskItemState::DONE->value,
                'skipped' => TaskItemState::SKIPPED->value,
                'failed' => TaskItemState::FAILED->value,
                'uncertain' => TaskItemState::UNCERTAIN->value,
                'since' => $since,
            ],
            [
                'running' => ArrayParameterType::STRING,
                'since' => Types::DATETIME_IMMUTABLE,
            ],
        );

        $summaries = [];

        foreach ($this->typeLabels as $type => $label) {
            $summaries[$type] = new TaskTypeSummary($type, $label, 0, 0, 0, 0, 0, 0);
        }

        foreach ($rows as $row) {
            $type = (string) $row['type'];
            $summaries[$type] = new TaskTypeSummary(
                $type,
                $this->typeLabels[$type] ?? $type,
                (int) $row['pending'],
                (int) $row['running'],
                (int) $row['done'],
                (int) $row['skipped'],
                (int) $row['failed'],
                (int) $row['uncertain'],
            );
        }

        ksort($summaries);

        return array_values($summaries);
    }

    /**
     * @param list<TaskJobStatus> $jobs
     *
     * @return array<string, string>
     */
    private function scopeLabels(array $jobs): array
    {
        $groupIds = array_values(array_unique(array_map(
            static fn (TaskJobStatus $job): int => $job->scopeId,
            array_filter($jobs, static fn (TaskJobStatus $job): bool => $job->scopeType === TaskScope::PAYMENT_GROUP),
        )));

        if ($groupIds === []) {
            return [];
        }

        $rows = $this->entityManager->getConnection()->fetchAllAssociative(
            'SELECT id, name FROM pa_group WHERE id IN (:ids)',
            ['ids' => $groupIds],
            ['ids' => ArrayParameterType::INTEGER],
        );

        $labels = [];

        foreach ($groupIds as $groupId) {
            $labels[TaskScope::PAYMENT_GROUP.':'.$groupId] = sprintf('Platební skupina #%d (smazaná)', $groupId);
        }

        foreach ($rows as $row) {
            $labels[TaskScope::PAYMENT_GROUP.':'.$row['id']] = sprintf('Platební skupina #%d %s', (int) $row['id'], (string) $row['name']);
        }

        return $labels;
    }

    /** @return list<TaskQueueRunRow> newest first */
    private function recentRuns(): array
    {
        $rows = $this->entityManager->getConnection()->fetchAllAssociative(
            'SELECT * FROM task_queue_run ORDER BY id DESC LIMIT '.self::RECENT_RUNS,
        );

        return array_map(
            static fn (array $row): TaskQueueRunRow => new TaskQueueRunRow(
                (int) $row['id'],
                new DateTimeImmutable((string) $row['started_at']),
                $row['finished_at'] === null ? null : new DateTimeImmutable((string) $row['finished_at']),
                (int) $row['taken'],
                (int) $row['done'],
                (int) $row['skipped'],
                (int) $row['failed'],
                (int) $row['uncertain'],
                (int) $row['postponed'],
                (int) $row['recovered'],
                $row['stop_reason'] === null ? null : TaskQueueRunStopReason::tryFrom((string) $row['stop_reason']),
                $row['pending_after'] === null ? null : (int) $row['pending_after'],
            ),
            $rows,
        );
    }

    private function lastDayTotals(DateTimeImmutable $since): TaskQueueRunTotals
    {
        $row = $this->entityManager->getConnection()->fetchAssociative(
            'SELECT COUNT(*) AS runs,
                    COALESCE(SUM(taken), 0) AS taken,
                    COALESCE(SUM(done), 0) AS done,
                    COALESCE(SUM(failed), 0) AS failed,
                    COALESCE(SUM(uncertain), 0) AS uncertain,
                    COALESCE(SUM(finished_at IS NULL), 0) AS unfinished,
                    AVG(TIMESTAMPDIFF(SECOND, started_at, finished_at)) AS average_seconds,
                    MAX(TIMESTAMPDIFF(SECOND, started_at, finished_at)) AS longest_seconds
                FROM task_queue_run
                WHERE started_at >= ?',
            [$since],
            [Types::DATETIME_IMMUTABLE],
        );

        $row = $row === false ? [] : $row;

        return new TaskQueueRunTotals(
            (int) ($row['runs'] ?? 0),
            (int) ($row['taken'] ?? 0),
            (int) ($row['done'] ?? 0),
            (int) ($row['failed'] ?? 0),
            (int) ($row['uncertain'] ?? 0),
            (int) ($row['unfinished'] ?? 0),
            isset($row['average_seconds']) ? (int) round((float) $row['average_seconds']) : null,
            isset($row['longest_seconds']) ? (int) $row['longest_seconds'] : null,
        );
    }
}
