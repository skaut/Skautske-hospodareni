<?php

declare(strict_types=1);

namespace App\Presentation\Payments\TaskQueueStatus;

use App\Components\Factories\TaskQueue\ITaskQueueStatusFactory;
use App\Components\TaskQueue\TaskQueueStatus;
use App\Model\Payment\PaymentService;
use App\Model\TaskQueue\TaskScope;
use App\Presentation\Payments\PaymentsBasePresenter;
use Nette\Application\Attributes\Persistent;

/**
 * Refresh of the panel of e-mails sent in the background of one payment group, polled by the group page
 * while a batch is active.
 *
 * Only the panel: no action of the group page runs (no visit, no list of payments, no import from the bank),
 * no menu and no layout. The rights are the same as on the group page and are checked in startup(), before
 * any signal of the panel can run, so nobody without the right to the group sees or changes its batches.
 */
final class TaskQueueStatusPresenter extends PaymentsBasePresenter
{
    #[Persistent]
    public int $id = 0;

    public function __construct(private PaymentService $model, private ITaskQueueStatusFactory $taskQueueStatusFactory)
    {
        parent::__construct();
    }

    protected function startup(): void
    {
        parent::startup();

        $group = $this->model->getGroup($this->id);
        if ($group === null || ! $this->canEditGroup($group)) {
            $this->denyAccess('Nemáte oprávnění pracovat s touto platební skupinou.');
        }

        $this->setLayout(false);
    }

    public function actionDefault(string $followed = ''): void
    {
        // The address serves the panel only; opened as a page it leads to the group.
        if (! $this->isAjax()) {
            $this->redirect(':Payments:Payment:default', ['id' => $this->id]);
        }

        $this['taskQueueStatus']->refresh($followed);
    }

    /** Nothing of the page is needed for the panel, so nothing of it is prepared. */
    protected function beforeRender(): void
    {
    }

    protected function createComponentTaskQueueStatus(): TaskQueueStatus
    {
        // The scope is the group checked in startup(), so the panel never shows batches of another group.
        $status = $this->taskQueueStatusFactory->create(TaskScope::paymentGroup($this->id), true);
        $status->setRefreshLink(fn (string $followed): string => $this->link('default', ['followed' => $followed]));
        $status->setFinishedAction('Načíst aktuální seznam plateb', $this->link(':Payments:Payment:default', ['id' => $this->id, 'do' => 'refreshPayments']));

        return $status;
    }
}
