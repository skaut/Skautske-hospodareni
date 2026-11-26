<?php

declare(strict_types=1);

namespace App\Model\TaskQueue;

use App\Model\TaskQueue\Repository\TaskQueueRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

use function array_values;
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
            $added = [];

            foreach (array_values($tasks) as $index => $task) {
                if ($this->repository->addItem($jobId, $task, $now)) {
                    $added[] = $index;
                }
            }

            if ($added === []) {
                $this->repository->deleteJob($jobId);

                return new EnqueueResult(null, 0, count($tasks));
            }

            return new EnqueueResult($jobId, count($added), count($tasks) - count($added), $added);
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

    /**
     * Cancels what still waits in a batch of the scope, e.g. reminders the user no longer wants to send.
     * What went out or is being sent right now stays. The tasks are not released: the user decided, so e.g.
     * a cancelled automatic reminder is not queued again on another day.
     *
     * @return int the cancelled tasks
     */
    public function cancelWaiting(int $jobId, TaskScope $scope, ?string $userName): int
    {
        $message = $userName === null ? 'Zrušeno.' : 'Zrušil(a) '.$userName.'.';

        return $this->repository->cancelWaitingOfJob($jobId, $scope, $message, new DateTimeImmutable());
    }

    /**
     * Lets tasks that wait for something outside the queue go right away once it is fixed, e.g. e-mails
     * waiting until the sender's account is connected again. Only tasks postponed with this exact reason
     * are released; e.g. e-mails postponed by a limit of Gmail keep waiting.
     *
     * @param string $message the reason the handler postponed the tasks with, see {@see Exception\TaskRetryLater}
     *
     * @return int the released tasks
     */
    public function releasePostponed(string $type, TaskScope $scope, string $message): int
    {
        return $this->repository->releasePostponed($type, $scope, $message, new DateTimeImmutable());
    }

    /** Hides a finished batch of the scope from the overview. */
    public function acknowledge(int $jobId, TaskScope $scope, ?string $userName): bool
    {
        return $this->repository->acknowledge($jobId, $scope, $userName, new DateTimeImmutable());
    }
}
