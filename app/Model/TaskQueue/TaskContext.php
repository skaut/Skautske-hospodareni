<?php

declare(strict_types=1);

namespace App\Model\TaskQueue;

use App\Model\TaskQueue\Exception\TaskLost;
use App\Model\TaskQueue\Repository\TaskQueueRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

/** What a handler gets for one task, and the two moments it reports back to the queue. */
final class TaskContext
{
    private bool $irreversible = false;

    private bool $completed = false;

    public function __construct(
        private ClaimedTask $task,
        private TaskQueueRepository $repository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /** @return array<string, mixed> */
    public function getPayload(): array
    {
        return $this->task->payload;
    }

    /** Who started the batch; null when it was started automatically. */
    public function getCreatedByName(): ?string
    {
        return $this->task->createdByName;
    }

    /**
     * Call right before the effect that cannot be taken back. When the run dies after this point,
     * the task is never run again.
     *
     * @throws TaskLost when another run took the task over meanwhile; nothing may happen then
     */
    public function beginIrreversible(): void
    {
        if (! $this->repository->markRunning($this->task, new DateTimeImmutable())) {
            throw new TaskLost('The task was taken over by another run.');
        }

        $this->irreversible = true;
    }

    /**
     * Records the result of the task together with its domain changes in one transaction.
     *
     * @param (callable(): void)|null $recordResult
     */
    public function complete(?callable $recordResult = null): void
    {
        $this->entityManager->wrapInTransaction(function () use ($recordResult): void {
            if ($recordResult !== null) {
                $recordResult();
            }

            if (! $this->repository->markDone($this->task, new DateTimeImmutable())) {
                throw new TaskLost('The task was taken over by another run.');
            }
        });

        $this->completed = true;
    }

    public function isIrreversibleStarted(): bool
    {
        return $this->irreversible;
    }

    public function isCompleted(): bool
    {
        return $this->completed;
    }
}
