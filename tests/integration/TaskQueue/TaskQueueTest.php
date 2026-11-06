<?php

declare(strict_types=1);

namespace App\Model\TaskQueue;

use App\Model\DTO\TaskQueue\TaskJobStatus;
use App\Model\TaskQueue\Entity\TaskItem;
use App\Model\TaskQueue\Entity\TaskQueueRun;
use App\Model\TaskQueue\ReadModel\Queries\TaskQueueOverviewQuery;
use App\Model\TaskQueue\ReadModel\Queries\TaskQueueStatusQuery;
use App\Model\TaskQueue\ReadModel\QueryHandlers\TaskQueueOverviewQueryHandler;
use App\Model\TaskQueue\ReadModel\QueryHandlers\TaskQueueStatusQueryHandler;
use App\Model\TaskQueue\ReadModel\TaskJobStatusReader;
use App\Model\TaskQueue\Repository\TaskQueueRepository;
use DateTimeImmutable;
use IntegrationTest;
use Psr\Log\NullLogger;

use function array_map;
use function microtime;
use function min;

final class TaskQueueTest extends IntegrationTest
{
    private TaskQueueRepository $repository;

    private TaskQueueProcessor $processor;

    private TaskQueue $queue;

    private RecordingTaskHandler $handler;

    private TaskQueueStatusQueryHandler $statusQuery;

    /** @return string[] */
    public function getTestedAggregateRoots(): array
    {
        return [TaskItem::class, TaskQueueRun::class];
    }

    protected function _before(): void
    {
        $this->tester->useConfigFiles(['config/doctrine.neon']);

        parent::_before();

        $this->handler = new RecordingTaskHandler();
        $this->repository = new TaskQueueRepository($this->entityManager);
        $this->processor = new TaskQueueProcessor($this->repository, $this->entityManager, new NullLogger(), [$this->handler], 20, 600);
        $this->queue = new TaskQueue($this->repository, $this->entityManager);
        $this->statusQuery = new TaskQueueStatusQueryHandler(new TaskJobStatusReader($this->entityManager));
    }

    public function testTasksRunOnceAndFinishedBatchLeavesTheOverview(): void
    {
        $scope = TaskScope::paymentGroup(1);
        $this->enqueue($scope, ['a1', 'a2']);

        $this->processAll();
        $this->processAll();

        self::assertSame(['a1', 'a2'], $this->handler->handled);
        self::assertSame(2, (int) $this->tester->grabNumRecords('task_queue_item', ['state' => TaskItemState::DONE->value]));
        self::assertSame([], $this->overview($scope));
    }

    public function testSameTaskCannotWaitInQueueTwice(): void
    {
        $scope = TaskScope::paymentGroup(1);

        $first = $this->enqueue($scope, ['x'], dedupPrefix: 'mail:');
        $second = $this->enqueue($scope, ['x'], dedupPrefix: 'mail:');

        self::assertSame(1, $first->queued);
        self::assertNull($second->jobId);
        self::assertSame(0, $second->queued);
        self::assertSame(1, $second->alreadyQueued);
        self::assertSame(1, (int) $this->tester->grabNumRecords('task_queue_job'));

        $this->processAll();

        // A finished task no longer blocks the same task from being queued again on purpose.
        self::assertSame(1, $this->enqueue($scope, ['x'], dedupPrefix: 'mail:')->queued);
    }

    public function testOnlyOneRunTakesTheTask(): void
    {
        $jobId = (int) $this->enqueue(TaskScope::paymentGroup(1), ['x'])->jobId;
        $itemId = $this->firstPendingItem($jobId);

        self::assertNotNull($this->repository->claim($itemId, 'firstrunfirstrunfirstrunfirstrun', new DateTimeImmutable()));
        self::assertNull($this->repository->claim($itemId, 'secondrunsecondrunsecondrunsecon', new DateTimeImmutable()));
    }

    public function testAbandonedClaimRunsAgainButInterruptedTaskNever(): void
    {
        $scope = TaskScope::paymentGroup(1);
        $jobId = (int) $this->enqueue($scope, ['claimed', 'interrupted'])->jobId;
        $longAgo = new DateTimeImmutable('-1 hour');

        // A run that died after taking the first task, and one that died while sending the second.
        $claimed = $this->repository->claim($this->firstPendingItem($jobId), 'deadrun1deadrun1deadrun1deadrun1', $longAgo);
        $interrupted = $this->repository->claim($this->firstPendingItem($jobId), 'deadrun2deadrun2deadrun2deadrun2', $longAgo);
        self::assertNotNull($claimed);
        self::assertNotNull($interrupted);
        self::assertTrue($this->repository->markRunning($interrupted, $longAgo));

        $this->processAll();

        self::assertSame(['claimed'], $this->handler->handled);
        $this->tester->seeInDatabase('task_queue_item', ['id' => $interrupted->itemId, 'state' => TaskItemState::UNCERTAIN->value]);

        [$job] = $this->overview($scope);
        self::assertSame(1, $job->uncertain);
        self::assertTrue($job->hasProblems());
        self::assertSame('interrupted', $job->getProblems(TaskItemState::UNCERTAIN)[0]->label);
    }

    public function testNoTaskStartsAfterTheDeadline(): void
    {
        $this->enqueue(TaskScope::paymentGroup(1), ['late']);

        $this->processor->processDue(microtime(true) - 1);

        self::assertSame([], $this->handler->handled);
        $this->tester->seeInDatabase('task_queue_item', ['state' => TaskItemState::PENDING->value]);
    }

    public function testBatchesTakeTurns(): void
    {
        $this->enqueue(TaskScope::paymentGroup(1), ['a1', 'a2', 'a3']);
        $this->enqueue(TaskScope::paymentGroup(2), ['b1']);

        $this->processAll();

        self::assertSame(['a1', 'b1', 'a2', 'a3'], $this->handler->handled);
    }

    public function testTasksWithoutEffectAreReleasedAndShown(): void
    {
        $scope = TaskScope::paymentGroup(1);
        $this->enqueue($scope, ['skipped', 'failed', 'sent'], ['skip', 'fail', 'done']);

        $this->processAll();

        self::assertSame(['skipped', 'failed'], $this->handler->released);
        [$job] = $this->overview($scope);
        self::assertTrue($job->isFinished());
        self::assertSame(1, $job->done);
        self::assertSame(1, $job->skipped);
        self::assertSame(1, $job->failed);
        self::assertSame('Nejde to.', $job->getProblems(TaskItemState::FAILED)[0]->message);
        self::assertSame('Není potřeba.', $job->getProblems(TaskItemState::SKIPPED)[0]->message);
    }

    public function testFailureOfWholeBatchStopsItsOtherTasks(): void
    {
        $scope = TaskScope::paymentGroup(1);
        $this->enqueue($scope, ['first', 'second', 'third'], ['abort', 'done', 'done']);

        $this->processAll();

        self::assertSame(['first'], $this->handler->handled);
        self::assertSame(['first', 'second', 'third'], $this->handler->released);
        self::assertSame(3, (int) $this->tester->grabNumRecords('task_queue_item', ['state' => TaskItemState::FAILED->value, 'message' => 'Nejde nic.']));
    }

    public function testCrashBeforeIrreversiblePartFailsAndAfterItIsUncertain(): void
    {
        $this->enqueue(TaskScope::paymentGroup(1), ['before', 'after'], ['crash', 'crashAfterIrreversible']);

        $this->processAll();

        $this->tester->seeInDatabase('task_queue_item', ['label' => 'before', 'state' => TaskItemState::FAILED->value]);
        $this->tester->seeInDatabase('task_queue_item', ['label' => 'after', 'state' => TaskItemState::UNCERTAIN->value]);
        self::assertSame(['before'], $this->handler->released);
    }

    public function testPostponedTaskWaitsAndFailsAfterLastAttempt(): void
    {
        $this->enqueue(TaskScope::paymentGroup(1), ['limited'], ['retry']);

        $this->processAll();
        $this->processAll();

        self::assertSame(['limited'], $this->handler->handled);
        $this->tester->seeInDatabase('task_queue_item', ['state' => TaskItemState::PENDING->value]);

        for ($attempt = 2; $attempt <= 3; ++$attempt) {
            $this->entityManager->getConnection()->executeStatement('UPDATE task_queue_item SET available_at = NOW() - INTERVAL 1 MINUTE');
            $this->processAll();
        }

        self::assertCount(3, $this->handler->handled);
        $this->tester->seeInDatabase('task_queue_item', ['state' => TaskItemState::FAILED->value, 'attempts' => 3]);
    }

    /** A step that would not finish within the request is not begun; the task waits for the next run. */
    public function testTaskWithoutTimeForItsIrreversibleStepWaitsForTheNextRun(): void
    {
        $this->enqueue(TaskScope::paymentGroup(1), ['late', 'next']);
        // Any irreversible step would end after the limit of the request.
        $processor = new TaskQueueProcessor($this->repository, $this->entityManager, new NullLogger(), [$this->handler], 20, 600, 30, 100);

        $processor->processDue(microtime(true) + 60);

        self::assertSame(['late'], $this->handler->handled, 'Run takes no more tasks once it is out of time');
        self::assertSame(2, (int) $this->tester->grabNumRecords('task_queue_item', ['state' => TaskItemState::PENDING->value, 'attempts' => 0]));

        $this->processAll();

        self::assertSame(2, (int) $this->tester->grabNumRecords('task_queue_item', ['state' => TaskItemState::DONE->value]));
    }

    public function testOverviewShowsOnlyBatchesOfTheScope(): void
    {
        $group1 = TaskScope::paymentGroup(1);
        $group2 = TaskScope::paymentGroup(2);
        $job1 = (int) $this->enqueue($group1, ['g1'])->jobId;
        $job2 = (int) $this->enqueue($group2, ['g2a', 'g2b'])->jobId;

        self::assertSame([$job1], $this->jobIds($this->overview($group1)));
        self::assertSame([$job2], $this->jobIds($this->overview($group2)));
        // Following a batch of another scope never shows it.
        self::assertSame([$job1], $this->jobIds($this->overview($group1, [$job2])));
        self::assertSame(2, $this->overview($group2)[0]->total);
    }

    public function testFollowedBatchShowsOnceItFinished(): void
    {
        $scope = TaskScope::paymentGroup(1);
        $jobId = (int) $this->enqueue($scope, ['a'])->jobId;

        $this->processAll();

        self::assertSame([], $this->overview($scope));
        [$job] = $this->overview($scope, [$jobId]);
        self::assertTrue($job->isFinished());
        self::assertSame(100, $job->getProgressPercent());
    }

    public function testBatchCanBeHiddenOnlyWithinItsScope(): void
    {
        $scope = TaskScope::paymentGroup(1);
        $jobId = (int) $this->enqueue($scope, ['broken'], ['fail'])->jobId;
        $this->processAll();

        self::assertFalse($this->queue->acknowledge($jobId, TaskScope::paymentGroup(2), 'Cizí'));
        self::assertCount(1, $this->overview($scope));

        self::assertTrue($this->queue->acknowledge($jobId, $scope, 'František Maša'));
        self::assertSame([], $this->overview($scope));
    }

    public function testOnlyRunsThatDidSomethingAreRecorded(): void
    {
        $this->processAll();
        self::assertSame(0, (int) $this->tester->grabNumRecords('task_queue_run'));

        $this->enqueue(TaskScope::paymentGroup(1), ['sent', 'broken'], ['done', 'fail']);
        $this->processAll();

        $this->tester->seeInDatabase('task_queue_run', [
            'taken' => 2,
            'done' => 1,
            'failed' => 1,
            'stop_reason' => TaskQueueRunStopReason::EMPTY->value,
            'pending_after' => 0,
        ]);
        $this->tester->dontSeeInDatabase('task_queue_run', ['finished_at' => null]);
    }

    public function testAdministrationOverviewShowsAllScopesAndRuns(): void
    {
        $this->enqueue(new TaskScope('test', 1), ['a1']);
        $this->enqueue(new TaskScope('test', 2), ['b1', 'b2'], ['done', 'fail']);
        $this->processor->processDue(microtime(true) + 60);
        $this->enqueue(new TaskScope('test', 3), ['c1']);

        $overview = (new TaskQueueOverviewQueryHandler($this->entityManager, new TaskJobStatusReader($this->entityManager), [$this->handler]))(
            new TaskQueueOverviewQuery(new DateTimeImmutable()),
        );

        [$type] = $overview->types;
        self::assertSame('Testovací úlohy', $type->label);
        self::assertSame(1, $type->pending);
        self::assertSame(2, $type->doneLastDay);
        self::assertSame(1, $type->failedLastDay);
        // The finished batch with a failure and the waiting batch, each of its own scope.
        self::assertSame(['test #2', 'test #3'], array_map($overview->getScopeLabel(...), $overview->jobs));
        self::assertCount(1, $overview->recentRuns);
        self::assertSame(3, $overview->recentRuns[0]->taken);
        self::assertSame(1, $overview->lastDay->runs);
    }

    public function testTaskIsAddedOnlyToExistingBatch(): void
    {
        $scope = TaskScope::paymentGroup(1);
        $jobId = (int) $this->enqueue($scope, ['first'])->jobId;

        self::assertTrue($this->queue->addToJob($jobId, new NewTask(['name' => 'second'], 'second')));
        self::assertFalse($this->queue->addToJob($jobId + 1000, new NewTask(['name' => 'lost'], 'lost')));
        self::assertSame(2, (int) $this->tester->grabNumRecords('task_queue_item'));
    }

    /**
     * @param list<string> $names
     * @param list<string> $behaviours
     */
    private function enqueue(TaskScope $scope, array $names, array $behaviours = [], ?string $dedupPrefix = null): EnqueueResult
    {
        $tasks = [];

        foreach ($names as $index => $name) {
            $tasks[] = new NewTask(
                ['name' => $name, 'behaviour' => $behaviours[$index] ?? 'done'],
                $name,
                $dedupPrefix === null ? null : $dedupPrefix.$name,
            );
        }

        return $this->queue->enqueue(RecordingTaskHandler::TYPE, $scope, 'Test', 'František Maša', $tasks);
    }

    private function processAll(): void
    {
        $this->processor->processDue(microtime(true) + 60);
    }

    private function firstPendingItem(int $jobId): int
    {
        $ids = array_map('intval', $this->tester->grabColumnFromDatabase('task_queue_item', 'id', ['job_id' => $jobId, 'state' => TaskItemState::PENDING->value]));

        return $ids === [] ? 0 : min($ids);
    }

    /**
     * @param int[] $followed
     *
     * @return list<TaskJobStatus>
     */
    private function overview(TaskScope $scope, array $followed = []): array
    {
        return ($this->statusQuery)(new TaskQueueStatusQuery($scope, new DateTimeImmutable(), $followed));
    }

    /**
     * @param list<TaskJobStatus> $jobs
     *
     * @return list<int>
     */
    private function jobIds(array $jobs): array
    {
        return array_map(static fn (TaskJobStatus $job): int => $job->id, $jobs);
    }
}
