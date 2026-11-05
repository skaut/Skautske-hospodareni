<?php

declare(strict_types=1);

namespace App\Components\TaskQueue;

use App\Components\BaseControl;
use App\Model\Common\Repositories\IUserRepository;
use App\Model\Common\Services\QueryBus;
use App\Model\DTO\TaskQueue\TaskJobStatus;
use App\Model\TaskQueue\ReadModel\Queries\TaskQueueStatusQuery;
use App\Model\TaskQueue\TaskQueue;
use App\Model\TaskQueue\TaskScope;
use DateTimeImmutable;
use Nette\Application\BadRequestException;
use Nette\Http\IResponse;

use function array_filter;
use function array_map;
use function explode;
use function implode;

/**
 * Progress of background batches of one scope, e.g. e-mails of one payment group. Everyone who may see
 * the scope sees the same progress. The page refreshes only this panel (frontend/ts/taskQueueStatus.ts)
 * while a batch is active.
 */
final class TaskQueueStatus extends BaseControl
{
    /** @var int[] batches the page showed as active before, so it can show that they finished */
    private array $followedJobIds = [];

    private ?string $finishedActionLabel = null;

    private ?string $finishedActionUrl = null;

    public function __construct(
        private TaskScope $scope,
        private bool $canAcknowledge,
        private QueryBus $queryBus,
        private TaskQueue $queue,
        private IUserRepository $users,
    ) {
    }

    /** A link offered once a batch finished, e.g. to load the list the batch changed. */
    public function setFinishedAction(string $label, string $url): void
    {
        $this->finishedActionLabel = $label;
        $this->finishedActionUrl = $url;
    }

    public function handleRefresh(string $followed = ''): void
    {
        $this->followedJobIds = array_map('intval', array_filter(explode(',', $followed), 'is_numeric'));
        $this->redrawControl('status');
    }

    public function handleAcknowledge(int $jobId): void
    {
        if (! $this->canAcknowledge) {
            throw new BadRequestException('Nemáte oprávnění skrýt přehled odesílání.', IResponse::S403_Forbidden);
        }

        $this->queue->acknowledge($jobId, $this->scope, $this->users->findCurrentUser()?->getName());

        if (! $this->getPresenter()->isAjax()) {
            $this->getPresenter()->redirect('this');
        }

        $this->redrawControl('status');
    }

    /** Shows the panel right away after the page queued a batch. */
    public function follow(): void
    {
        $this->redrawControl('status');
    }

    public function render(): void
    {
        $jobs = $this->queryBus->handle(new TaskQueueStatusQuery($this->scope, new DateTimeImmutable(), $this->followedJobIds));
        $activeJobIds = array_map(
            static fn (TaskJobStatus $job): int => $job->id,
            array_filter($jobs, static fn (TaskJobStatus $job): bool => ! $job->isFinished()),
        );

        $this->template->setFile(__DIR__.'/templates/TaskQueueStatus.latte');
        $this->template->setParameters([
            'jobs' => $jobs,
            'isActive' => $activeJobIds !== [],
            'refreshUrl' => $this->link('refresh!', ['followed' => implode(',', $activeJobIds)]),
            'canAcknowledge' => $this->canAcknowledge,
            'finishedActionLabel' => $this->finishedActionLabel,
            'finishedActionUrl' => $this->finishedActionUrl,
        ]);
        $this->template->render();
    }
}
