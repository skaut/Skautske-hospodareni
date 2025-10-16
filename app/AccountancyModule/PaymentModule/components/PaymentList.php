<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule\Components;

use App\AccountancyModule\Components\BaseControl;
use App\AccountancyModule\Components\DataGrid;
use App\AccountancyModule\Factories\GridFactory;
use App\AccountancyModule\Grids\DtoListDataSource;
use Model\Common\Services\CommandBus;
use Model\Common\Services\QueryBus;
use Model\DTO\Payment\Payment;
use Model\Google\Exception\OAuthNotSet;
use Model\Google\InvalidOAuth;
use Model\Payment\Commands\Mailing\SendPaymentInfo;
use Model\Payment\Commands\Mailing\SendPaymentReminder;
use Model\Payment\EmailType;
use Model\Payment\InvalidBankAccount;
use Model\Payment\Payment\State;
use Model\Payment\PaymentClosed;
use Model\Payment\PaymentNotFound;
use Model\Payment\ReadModel\Queries\GroupEmailQuery;
use Model\Payment\ReadModel\Queries\PaymentListQuery;
use Model\PaymentService;

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
        $this->template->setFile(__DIR__ . '/templates/PaymentList.latte');
        $this->template->render();
    }

    protected function createComponentGrid(): DataGrid
    {
        $email = $this->queryBus->handle(new GroupEmailQuery($this->groupId, EmailType::get(EmailType::PAYMENT_REMINDER)));
        $grid  = $this->gridFactory->createSimpleGrid(
            __DIR__ . '/templates/PaymentList.grid.latte',
            [
                'isEditable' => $this->isEditable,
                'isReminderSendActive' => $email->isEnabled(),
            ],
        );
        $grid->setRememberState(false, true);
        $grid->setColumnsHideable();

        $grid->addGroupButtonAction('Odeslat email')->onClick[]    = [$this, 'sendMail'];
        $grid->addGroupButtonAction('Odeslat upomínku')->onClick[] = [$this, 'sendReminder'];
        $grid->addGroupButtonAction('Zaplaceno')->onClick[]        = [$this, 'setPay'];
        $grid->addGroupButtonAction('Zrušit')->onClick[]           = [$this, 'setCancel'];

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
                $data          = $dataSource->getData();

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

    /** @param array<int,int> $ids */
    public function sendMail(array $ids): void
    {
        $count = 0;
        foreach ($ids as $id) {
            try {
                $this->commandBus->handle(new SendPaymentInfo($id));
                $count++;
            } catch (OAuthNotSet) {
                $this->flashMessage(EmailButton::NO_MAILER_MESSAGE, 'warning');
                $this->redirect('this');
            } catch (InvalidBankAccount) {
                $this->flashMessage(EmailButton::NO_BANK_ACCOUNT_MESSAGE, 'warning');
                $this->redirect('this');
            } catch (InvalidOAuth $e) {
                $this->flashMessage($e->getExplainedMessage(), 'danger');
                $this->presenter->redirect('this');
            } catch (PaymentClosed) {
                $this->flashMessage('Nelze odeslat uzavřenou platbu', 'warning');
            }
        }

        if ($count === 1) {
            $this->presenter->flashMessage($count . ' informační e-mail odeslán', 'info');
        } else {
            $this->presenter->flashMessage($count . ' Informačních e-mailů odesláno', 'info');
        }

        $this->presenter->redirect('this');
    }

    /** @param array<int,int> $ids */
    public function sendReminder(array $ids): void
    {
        $count = 0;
        foreach ($ids as $id) {
            try {
                $this->commandBus->handle(new SendPaymentReminder($id));
                $count++;
            } catch (OAuthNotSet) {
                $this->flashMessage(EmailButton::NO_MAILER_MESSAGE, 'warning');
                $this->redirect('this');
            } catch (InvalidBankAccount) {
                $this->flashMessage(EmailButton::NO_BANK_ACCOUNT_MESSAGE, 'warning');
                $this->redirect('this');
            } catch (InvalidOAuth $e) {
                $this->flashMessage($e->getExplainedMessage(), 'danger');
                $this->presenter->redirect('this');
            } catch (PaymentClosed) {
                $this->flashMessage('Nelze odeslat uzavřenou platbu', 'warning');
            }
        }

        if ($count > 0) {
            if ($count === 1) {
                $this->presenter->flashMessage($count . ' upomínkový e-mailů odeslán', 'info');
            } else {
                $this->presenter->flashMessage($count . ' upomínkových e-mailů odesláno', 'info');
            }
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
            } catch (PaymentClosed) {
                $this->flashMessage('Tato platba už je uzavřená', 'danger');
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
            } catch (PaymentClosed) {
                $this->flashMessage('Tato platba už je uzavřená: ' . $id, 'warning');
            }
        }

        $this->presenter->redirect('this');
    }
}
