<?php

declare(strict_types=1);

namespace App\Model\TaskQueue;

final class EnqueueResult
{
    /**
     * @param int|null  $jobId         null when no task was added
     * @param int       $queued        tasks added to the queue
     * @param int       $alreadyQueued tasks left out because the same task already waits in the queue
     * @param list<int> $addedIndexes  positions of the added tasks in the list given to the queue
     */
    public function __construct(
        public readonly ?int $jobId,
        public readonly int $queued,
        public readonly int $alreadyQueued,
        public readonly array $addedIndexes = [],
    ) {
    }
}
