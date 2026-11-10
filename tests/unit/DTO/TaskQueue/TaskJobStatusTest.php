<?php

declare(strict_types=1);

namespace App\Model\DTO\TaskQueue;

use App\Model\TaskQueue\TaskItemState;
use Codeception\Test\Unit;
use DateTimeImmutable;

final class TaskJobStatusTest extends Unit
{
    /** A whole batch failing for one reason shows the reason once, with all its tasks under it. */
    public function testProblemsAreGroupedByReason(): void
    {
        $status = $this->jobStatus([
            new TaskProblem('Platba 1', TaskItemState::FAILED, 'Propojení přestalo platit.'),
            new TaskProblem('Platba 2', TaskItemState::SKIPPED, 'Platba je už uzavřená.'),
            new TaskProblem('Platba 3', TaskItemState::FAILED, 'Platba nemá vyplněný e-mail.'),
            new TaskProblem('Platba 4', TaskItemState::FAILED, 'Propojení přestalo platit.'),
        ]);

        self::assertSame(
            [
                'Propojení přestalo platit.' => ['Platba 1', 'Platba 4'],
                'Platba nemá vyplněný e-mail.' => ['Platba 3'],
            ],
            $status->getProblemsByReason(TaskItemState::FAILED),
        );
        self::assertSame([], $status->getProblemsByReason(TaskItemState::UNCERTAIN));
    }

    /** @param list<TaskProblem> $problems */
    private function jobStatus(array $problems): TaskJobStatus
    {
        return new TaskJobStatus(1, 'payment_email', 'payment_group', 1, 'E-maily', new DateTimeImmutable(), null, 4, 0, 0, 1, 3, 0, false, $problems);
    }
}
