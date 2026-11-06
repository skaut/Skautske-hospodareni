<?php

declare(strict_types=1);

namespace App\Model\TaskQueue;

use App\Model\TaskQueue\Repository\TaskQueueRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

use function count;

/**
 * Queue of tasks run in the background by the cron, see {@see TaskQueueProcessor}.
 *
 * E-mails of payments go through this queue, so a request never waits for slow calls and no e-mail
 * is sent twice.
 */
final class TaskQueue
{
    public function __construct(
        private TaskQueueRepository $repository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Adds one batch of tasks. Runs inside the caller's transaction when there is one, so the tasks
     * are queued only when the change that caused them is saved.
     *
     * @param string      $type          task type of the handler, see {@see TaskHandler::getType()}
     * @param string      $label         what the batch does in words, e.g. „Upomínky“
     * @param string|null $createdByName who started the batch; null when it starts automatically
     * @param NewTask[]   $tasks
     */
    public function enqueue(string $type, TaskScope $scope, string $label, ?string $createdByName, array $tasks): EnqueueResult
    {
        if ($tasks === []) {
            return new EnqueueResult(null, 0, 0);
        }

        return $this->entityManager->wrapInTransaction(function () use ($type, $scope, $label, $createdByName, $tasks): EnqueueResult {
            $now = new DateTimeImmutable();
            $jobId = $this->repository->createJob($type, $scope, $label, $createdByName, $now);
            $queued = 0;

            foreach ($tasks as $task) {
                if ($this->repository->addItem($jobId, $task, $now)) {
                    ++$queued;
                }
            }

            if ($queued === 0) {
                $this->repository->deleteJob($jobId);

                return new EnqueueResult(null, 0, count($tasks));
            }

            return new EnqueueResult($jobId, $queued, count($tasks) - $queued);
        });
    }

    /**
     * Adds one more task to a batch started earlier in this request, e.g. the next confirmation e-mail
     * of the same group.
     *
     * @return bool false when the batch no longer exists or the same task already waits in the queue
     */
    public function addToJob(int $jobId, NewTask $task): bool
    {
        return $this->repository->addItemToExistingJob($jobId, $task, new DateTimeImmutable());
    }

    /** Hides a finished batch of the scope from the overview. */
    public function acknowledge(int $jobId, TaskScope $scope, ?string $userName): bool
    {
        return $this->repository->acknowledge($jobId, $scope, $userName, new DateTimeImmutable());
    }

    public function isQueued(string $dedupKey): bool
    {
        return $this->repository->hasActiveItem($dedupKey);
    }
}
