<?php

declare(strict_types=1);

namespace App\Model\DTO\TaskQueue;

final class TaskQueueOverview
{
    /**
     * @param list<TaskTypeSummary> $types
     * @param list<TaskJobStatus>   $jobs        active batches and finished ones with a problem, of all scopes
     * @param array<string, string> $scopeLabels by "scope_type:scope_id"
     * @param list<TaskQueueRunRow> $recentRuns  newest first
     */
    public function __construct(
        public readonly array $types,
        public readonly array $jobs,
        public readonly array $scopeLabels,
        public readonly array $recentRuns,
        public readonly TaskQueueRunTotals $lastDay,
    ) {
    }

    public function getScopeLabel(TaskJobStatus $job): string
    {
        return $this->scopeLabels[$job->scopeType.':'.$job->scopeId] ?? $job->scopeType.' #'.$job->scopeId;
    }
}
