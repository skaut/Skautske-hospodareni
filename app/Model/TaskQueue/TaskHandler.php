<?php

declare(strict_types=1);

namespace App\Model\TaskQueue;

use App\Model\TaskQueue\Exception\TaskFailed;
use App\Model\TaskQueue\Exception\TaskRetryLater;
use App\Model\TaskQueue\Exception\TaskSkipped;

/**
 * Runs queued tasks of one type. Handlers are found by type, every service implementing this
 * interface is registered automatically.
 *
 * A handler ends a task by returning (done), or by throwing {@see TaskSkipped}, {@see TaskFailed}
 * or {@see TaskRetryLater}. It calls {@see TaskContext::beginIrreversible()} right before the effect
 * that cannot be taken back, and records the result with {@see TaskContext::complete()}.
 */
interface TaskHandler
{
    public function getType(): string;

    /** What the tasks are in words, for the administration, e.g. „E-maily plateb“. */
    public function getLabel(): string;

    public function handle(TaskContext $context): void;

    /**
     * Called when a task ends without its effect (skipped, failed or postponed), in the same
     * transaction as the state change, so the handler can undo what it reserved for the task.
     * It must use plain SQL, the entity manager may be closed after a failure.
     *
     * @param array<string, mixed> $payload
     */
    public function release(array $payload): void;
}
