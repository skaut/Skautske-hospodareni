<?php

declare(strict_types=1);

namespace App\Model\TaskQueue\ReadModel\Queries;

use App\Model\TaskQueue\ReadModel\QueryHandlers\CountWaitingTasksQueryHandler;
use App\Model\TaskQueue\TaskScope;

/**
 * How many tasks of the scope still wait or are being processed, e.g. e-mails of one payment group.
 *
 * @see CountWaitingTasksQueryHandler
 */
final class CountWaitingTasksQuery
{
    public function __construct(public readonly TaskScope $scope)
    {
    }
}
