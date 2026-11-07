<?php

declare(strict_types=1);

namespace App\Model\TaskQueue\ReadModel\Queries;

use App\Model\TaskQueue\ReadModel\QueryHandlers\TaskQueueStatusQueryHandler;
use App\Model\TaskQueue\TaskScope;
use DateTimeImmutable;

/**
 * Batches of one scope to show in the overview: the active ones, finished ones with a problem that
 * nobody hid yet, and the ones the page is still following, so it can show they finished.
 *
 * @see TaskQueueStatusQueryHandler
 */
final class TaskQueueStatusQuery
{
    /** @param int[] $followedJobIds batches the page showed before; other scopes' batches are never returned */
    public function __construct(
        private TaskScope $scope,
        private DateTimeImmutable $now,
        private array $followedJobIds = [],
    ) {
    }

    public function getScope(): TaskScope
    {
        return $this->scope;
    }

    public function getNow(): DateTimeImmutable
    {
        return $this->now;
    }

    /** @return int[] */
    public function getFollowedJobIds(): array
    {
        return $this->followedJobIds;
    }
}
