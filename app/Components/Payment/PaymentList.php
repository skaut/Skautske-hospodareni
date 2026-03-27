<?php

declare(strict_types=1);

namespace App\Components\Payment;

use App\Components\BaseControl;
use App\Components\DataGrid;
use App\Components\Grids\DtoListDataSource;
use App\Components\Grids\GridFactory;
use App\Model\Common\Services\CommandBus;
use App\Model\Common\Services\QueryBus;
use App\Model\DTO\Payment\Payment;
use App\Model\Google\Exception\OAuthNotSet;
use App\Model\Google\InvalidOAuth;
use App\Model\Payment\Commands\Mailing\SendPaymentInfo;
use App\Model\Payment\Commands\Mailing\SendPaymentReminder;
use App\Model\Payment\EmailTemplateNotSet;
use App\Model\Payment\EmailType;
use App\Model\Payment\InvalidBankAccount;
use App\Model\Payment\Payment\State;
use App\Model\Payment\PaymentClosed;
use App\Model\Payment\PaymentHasNoEmails;
use App\Model\Payment\PaymentNotFound;
use App\Model\Payment\PaymentService;
use App\Model\Payment\ReadModel\Queries\GroupEmailQuery;
use App\Model\Payment\ReadModel\Queries\PaymentListQuery;

use function array_flip;
use function array_reverse;
use function strcoll;
use function usort;

final class PaymentList extends BaseControl
{
    private const STATE_ORDER = [
        State::PREPARING,
        State::COMPLETED,
        State::CANCELED,
    ];

    public function __construct(private PaymentService $model, private CommandBus $commandBus, private int $groupId, private bool $isEditable, private QueryBus $queryBus, private GridFactory $gridFactory)
    {
    }

    public function render(): void
    {
        $this->template->setFile(__DIR__.'/templates/PaymentList.latte');
        $this->template->render();
    }

    protected function createComponentGrid(): DataGrid
    {
        $email = $this->queryBus->handle(new GroupEmailQuery($this->groupId, EmailType::get(EmailType::PAYMENT_REMINDER)));
        $group = $this->model->getGroup($this->groupId);
        $grid = $this->gridFactory->createSimpleGrid(
            __DIR__.'/templates/PaymentList.grid.latte',
            [
                'isEditable' => $this->isEditable,
                'isReminderSendActive' => $email !== null && $email->isEnabled(),
                'bankAccountId' => $group?->getBankAccountId(),
            ],
        );
        $grid->setRememberState(false, true);
        $grid->setColumnsHideable();

        $grid->addGroupButtonAction('Odeslat email')->onClick[] = [$this, 'sendMail'];
        if ($email !== null && $email->isEnabled()) {
            $grid->addGroupButtonAction('Odeslat upomínku')->onClick[] = [$this, 'sendReminder'];
        }

        $grid->addGroupButtonAction('Zaplaceno')->onClick[] = [$this, 'setPay'];
        $grid->addGroupButtonAction('Zrušit')->onClick[] = [$this, 'setCancel'];

        $grid->addColumnText('name', 'Název/účel')
            ->setSortable()
            ->setSortableCallback(function (DtoListDataSource $dataSource, array $sort): DtoListDataSource {
                $data = $dataSource->getData();

                usort($data, fn (Payment $a, Payment $b) => strcoll($a->getName(), $b->getName()));

                return new DtoListDataSource($sort['name'] === DataGrid::SORT_ASC ? $data : array_reverse($data));
            })
            ->getElementPrototype('td')
            ->setAttribute('class', 'w-18');

        $grid->addColumnText('recipientsString', 'E-mail')
            ->addCellAttributes(['class' => 'small'])
            ->setSortable();

        $grid->addColumnText('amount', 'Částka')
            ->setSortable();

        $grid->addColumnText('variableSymbol', 'VS')
            ->setSortable();

        $grid->addColumnText('constantSymbol', 'KS')
            ->setSortable()
            ->setDefaultHide();

        $grid->addColumnDateTime('dueDate', 'Splatnost')
            ->setSortable();

        $grid->addColumnDateTime('closedAt', 'Zaplaceno')
            ->setSortable();

        $grid->addColumnDateTime('Note', 'Poznámka')
            ->setSortable()
            ->setDefaultHide();

        $grid->addColumnText('state', 'Stav')
            ->setSortable()
            ->setSortableCallback(function (DtoListDataSource $dataSource, array $sort): DtoListDataSource {
                $statePriority = array_flip(self::STATE_ORDER);
                $data = $dataSource->getData();

                usort($data, function (Payment $a, Payment $b) use ($statePriority): int {
                    return $statePriority[$a->getState()->toString()] <=> $statePriority[$b->getState()->toString()];
                });

                return new DtoListDataSource($sort['state'] === DataGrid::SORT_ASC ? $data : array_reverse($data));
            });

        $grid->addColumnText('actions', 'Akce');

        $grid->setDataSource(new DtoListDataSource($this->queryBus->handle(new PaymentListQuery($this->groupId))));

        $grid->setDefaultSort(['state' => DataGrid::SORT_ASC]);

        return $grid;
    }

    public function handleSend(int $pid): void
    {
        $payment = $this->model->findPayment($pid);

        if ($payment === null) {
            $this->presenter->flashMessage('Zadaná platba neexistuje', 'danger');
            $this->presenter->redirect('this');
        }

        if (empty($payment->getEmailRecipients())) {
            $this->presenter->flashMessage('Platba nemá vyplněný e-mail', 'danger');
            $this->presenter->redirect('this');
        }

        $this->sendMail([$pid]);
    }

    public function handleSendReminder(int $pid): void
    {
        $payment = $this->model->findPayment($pid);

        if ($payment === null) {
            $this->presenter->flashMessage('Zadaná platba neexistuje', 'danger');
            $this->presenter->redirect('this');
        }

        if (empty($payment->getEmailRecipients())) {
            $this->presenter->flashMessage('Platba nemá vyplněný e-mail', 'danger');
            $this->presenter->redirect('this');
        }

        $this->sendReminder([$pid]);
    }

    public function handleComplete(int $pid): void
    {
        $this->setPay([$pid]);
    }

    public function handleCancel(int $pid): void
    {
        $this->setCancel([$pid]);
    }

    /** @param array<int,int> $ids */
    public function sendMail(array $ids): void
    {
        $count = 0;
        foreach ($ids as $id) {
            try {
                $this->commandBus->handle(new SendPaymentInfo($id));
                ++$count;
            } catch (OAuthNotSet) {
                $this->flashMessage(EmailButton::NO_MAILER_MESSAGE, 'warning');
            } catch (InvalidBankAccount) {
                $this->flashMessage(EmailButton::NO_BANK_ACCOUNT_MESSAGE, 'warning');
            } catch (InvalidOAuth $e) {
                $this->flashMessage($e->getExplainedMessage(), 'danger');
            } catch (PaymentClosed $e) {
                $this->flashMessage($e->getMessage(), 'warning');
            } catch (PaymentHasNoEmails $e) {
                $this->flashMessage($e->getMessage(), 'warning');
            }
        }

        if ($count === 1) {
            $this->presenter->flashMessage($count.' informační e-mail odeslán', 'info');
        } else {
            $this->presenter->flashMessage($count.' Informačních e-mailů odesláno', 'info');
        }

        $this->presenter->redirect('this');
    }

    /** @param array<int,int> $ids */
    public function sendReminder(array $ids): void
    {
        $count = 0;
        try {
            foreach ($ids as $id) {
                try {
                    $this->commandBus->handle(new SendPaymentReminder($id));
                    ++$count;
                } catch (OAuthNotSet) {
                    $this->flashMessage(EmailButton::NO_MAILER_MESSAGE, 'warning');
                } catch (InvalidBankAccount) {
                    $this->flashMessage(EmailButton::NO_BANK_ACCOUNT_MESSAGE, 'warning');
                } catch (InvalidOAuth $e) {
                    $this->flashMessage($e->getExplainedMessage(), 'danger');
                } catch (PaymentClosed $e) {
                    $this->flashMessage($e->getMessage(), 'warning');
                } catch (PaymentHasNoEmails $e) {
                    $this->flashMessage($e->getMessage(), 'warning');
                }
            }

            if ($count === 1) {
                $this->presenter->flashMessage($count.' upomínkový e-mailů odeslán', 'info');
            } else {
                $this->presenter->flashMessage($count.' upomínkových e-mailů odesláno', 'info');
            }
        } catch (EmailTemplateNotSet) {
            $this->flashMessage('Platební skupina nemá povolené upomínky', 'warning');
        }

        $this->presenter->redirect('this');
    }

    /** @param array<int,int> $ids */
    public function setPay(array $ids): void
    {
        if (! $this->isEditable) {
            $this->flashMessage('Nejste oprávněni k uzavření platby!', 'danger');
            $this->redirect('this');
        }

        foreach ($ids as $id) {
            try {
                $this->model->completePayment($id);
                $this->flashMessage('Platba byla zaplacena.');
            } catch (PaymentClosed $e) {
                $this->flashMessage($e->getMessage(), 'warning');
            } catch (InvalidOAuth $exc) {
                $this->flashMessage($exc->getExplainedMessage(), 'danger');
            }
        }

        $this->presenter->redirect('this');
    }

    /** @param array<int,int> $ids */
    public function setCancel(array $ids): void
    {
        foreach ($ids as $id) {
            try {
                $this->model->cancelPayment($id);
                $this->flashMessage('Platba byla uzavřena');
            } catch (PaymentNotFound) {
                $this->flashMessage('Platba nenalezena!', 'danger');
            } catch (PaymentClosed $e) {
                $this->flashMessage($e->getMessage(), 'warning');
            }
        }

        $this->presenter->redirect('this');
    }
}
