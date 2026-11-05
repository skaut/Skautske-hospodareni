<?php

declare(strict_types=1);

namespace App\Model\TaskQueue;

final class EnqueueResult
{
    /**
     * @param int|null $jobId         null when no task was added
     * @param int      $queued        tasks added to the queue
     * @param int      $alreadyQueued tasks left out because the same task already waits in the queue
     */
    public function __construct(
        public readonly ?int $jobId,
        public readonly int $queued,
        public readonly int $alreadyQueued,
    ) {
    }
}
