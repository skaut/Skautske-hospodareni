<?php

declare(strict_types=1);

namespace App\Model\DTO\TaskQueue;

use App\Model\TaskQueue\TaskItemState;
use DateTimeImmutable;

use function array_filter;
use function array_values;
use function intdiv;

final class TaskJobStatus
{
    /**
     * @param list<TaskProblem>      $problems
     * @param DateTimeImmutable|null $postponedUntil  when all waiting tasks were postponed, e.g. by a limit of Gmail
     * @param string|null            $postponedReason why they were postponed, in words for people
     */
    public function __construct(
        public readonly int $id,
        public readonly string $type,
        public readonly string $scopeType,
        public readonly int $scopeId,
        public readonly string $label,
        public readonly DateTimeImmutable $createdAt,
        public readonly ?string $createdByName,
        public readonly int $total,
        public readonly int $done,
        public readonly int $active,
        public readonly int $skipped,
        public readonly int $failed,
        public readonly int $uncertain,
        public readonly bool $stalled,
        public readonly array $problems,
        public readonly ?DateTimeImmutable $postponedUntil = null,
        public readonly ?string $postponedReason = null,
    ) {
    }

    public function isFinished(): bool
    {
        return $this->active === 0;
    }

    /** Share of tasks that are no longer waiting, in percent. */
    public function getProgressPercent(): int
    {
        return $this->total === 0 ? 100 : intdiv(($this->total - $this->active) * 100, $this->total);
    }

    public function hasProblems(): bool
    {
        return $this->failed > 0 || $this->uncertain > 0;
    }

    /** @return list<TaskProblem> */
    public function getProblems(TaskItemState $state): array
    {
        return array_values(array_filter($this->problems, static fn (TaskProblem $problem): bool => $problem->state === $state));
    }

    /**
     * Tasks of one state grouped by why they ended so, e.g. a whole batch failing for one reason shows it once.
     *
     * @return array<string, list<string>> reason => labels of the tasks, in the order of the tasks
     */
    public function getProblemsByReason(TaskItemState $state): array
    {
        $byReason = [];

        foreach ($this->getProblems($state) as $problem) {
            $byReason[$problem->message ?? ''][] = $problem->label;
        }

        return $byReason;
    }
}
