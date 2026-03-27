<?php

declare(strict_types=1);

namespace App\Components\Payment;

use App\Components\BaseControl;
use App\Model\Common\Services\CommandBus;
use App\Model\Common\Services\QueryBus;
use App\Model\DTO\Payment\Group;
use App\Model\DTO\Payment\Payment;
use App\Model\Google\Exception\OAuthNotSet;
use App\Model\Google\InvalidOAuth;
use App\Model\Payment\Commands\Mailing\SendPaymentInfo;
use App\Model\Payment\Commands\Mailing\SendPaymentReminder;
use App\Model\Payment\EmailNotSet;
use App\Model\Payment\EmailTemplateNotSet;
use App\Model\Payment\EmailType;
use App\Model\Payment\InvalidBankAccount;
use App\Model\Payment\MailingService;
use App\Model\Payment\ReadModel\Queries\GroupEmailQuery;

use function array_filter;

class EmailButton extends BaseControl
{
    public const NO_MAILER_MESSAGE = 'Nemáte nastavený mail pro odesílání u skupiny';
    public const NO_BANK_ACCOUNT_MESSAGE = 'Skupina nemá nastavený bankovní účet';

    public const NO_TEMPLATE_ASSIGNED = 'Skupina nemá nastavenou šablonu pro upomínku';

    /** @param Payment[] $payments */
    public function __construct(private QueryBus $queryBus, private CommandBus $commandBus, private MailingService $mailing, private bool $isEditable, private array $payments, private ?Group $group)
    {
    }

    public function render(): void
    {
        $email = $this->queryBus->handle(new GroupEmailQuery($this->group->id, EmailType::get(EmailType::PAYMENT_REMINDER)));
        $paymentsForSendEmail = $this->paymentsAvailableForGroupInfoSending($this->payments);

        $this->template->setParameters([
            'canSend' => $this->canSend(),
            'isReminderSendActive' => $email !== null && $email->isEnabled(),
            'isGroupSendActive' => $this->group->getState() === 'open' && ! empty($paymentsForSendEmail),
        ]);
        $this->template->setFile(__DIR__.'/templates/EmailButton.latte');
        $this->template->render();
    }

    public function canSend(): bool
    {
        return ! ($this->group->getOauthId() === null) && ! ($this->group->getBankAccountId() === null);
    }

    public function renderLight(): void
    {
        $this->template->setParameters(['style' => 'light']);
        $this->render();
    }

    /**
     * rozešle všechny neposlané e-maily.
     */
    public function handleSendGroup(): void
    {
        $this->sendPaymentInfoEmails($this->paymentsAvailableForGroupInfoSending($this->payments));
    }

    public function handleSendGroupReminder(): void
    {
        $this->sendPaymentReminderEmails($this->paymentsAvailableForGroupReminderSending($this->payments));
    }

    public function handleSendTest(): void
    {
        if (! $this->isEditable) {
            $this->presenter->flashMessage('Neplatný požadavek na odeslání testovacího e-mailu!', 'danger');
            $this->presenter->redirect('this');
        }

        try {
            $email = $this->mailing->sendTestMail($this->group->id);
            $this->presenter->flashMessage('Testovací e-mail byl odeslán na '.$email.'.');
        } catch (OAuthNotSet) {
            $this->presenter->flashMessage(self::NO_MAILER_MESSAGE, 'warning');
        } catch (InvalidOAuth $e) {
            $this->oauthError($e);
        } catch (InvalidBankAccount) {
            $this->presenter->flashMessage(self::NO_BANK_ACCOUNT_MESSAGE, 'warning');
        } catch (EmailNotSet) {
            $this->flashMessage('Nemáte nastavený e-mail ve skautisu, na který by se odeslal testovací e-mail!', 'danger');
        }

        $this->redirect('this');
    }

    /** @param Payment[] $payments */
    private function sendPaymentInfoEmails(array $payments): void
    {
        $sentCount = 0;

        try {
            foreach ($payments as $payment) {
                $this->commandBus->handle(new SendPaymentInfo($payment->getId()));
                ++$sentCount;
            }
        } catch (OAuthNotSet) {
            $this->presenter->flashMessage(self::NO_MAILER_MESSAGE, 'warning');
            $this->presenter->redirect('this');
        } catch (InvalidBankAccount) {
            $this->presenter->flashMessage(self::NO_BANK_ACCOUNT_MESSAGE, 'warning');
            $this->presenter->redirect('this');
        } catch (InvalidOAuth $e) {
            $this->oauthError($e);
            $this->presenter->redirect('this');
        }

        if ($sentCount > 0) {
            $this->presenter->flashMessage(
                $sentCount === 1
                    ? 'Informační e-mail byl odeslán'
                    : 'Informační e-maily ('.$sentCount.') byly odeslány',
                'success',
            );
        }

        $this->presenter->redirect('this');
    }

    /** @param Payment[] $payments */
    private function sendPaymentReminderEmails(array $payments): void
    {
        $sentCount = 0;

        try {
            foreach ($payments as $payment) {
                $this->commandBus->handle(new SendPaymentReminder($payment->getId()));
                ++$sentCount;
            }
        } catch (OAuthNotSet) {
            $this->presenter->flashMessage(self::NO_MAILER_MESSAGE, 'warning');
            $this->presenter->redirect('this');
        } catch (InvalidBankAccount) {
            $this->presenter->flashMessage(self::NO_BANK_ACCOUNT_MESSAGE, 'warning');
            $this->presenter->redirect('this');
        } catch (InvalidOAuth $e) {
            $this->oauthError($e);
            $this->presenter->redirect('this');
        } catch (EmailTemplateNotSet) {
            $this->presenter->flashMessage(self::NO_TEMPLATE_ASSIGNED, 'warning');
            $this->presenter->redirect('this');
        }

        if ($sentCount > 0) {
            $this->presenter->flashMessage(
                $sentCount === 1
                    ? 'E-mail s upomínkou byl odeslán'
                    : 'E-maily s upomínkou  byly odeslány (celkem: '.$sentCount.')',
                'success',
            );
        }

        $this->presenter->redirect('this');
    }

    /**
     * @param Payment[] $payments
     *
     * @return Payment[]
     */
    private function paymentsAvailableForGroupInfoSending(array $payments): array
    {
        return array_filter(
            $payments,
            function (Payment $p) {
                return ! $p->isClosed() && ! empty($p->getEmailRecipients()) && $p->getSentEmails() === [];
            },
        );
    }

    /**
     * @param Payment[] $payments
     *
     * @return Payment[]
     */
    private function paymentsAvailableForGroupReminderSending(array $payments): array
    {
        return array_filter(
            $payments,
            function (Payment $p) {
                return $p->isOverdue();
            },
        );
    }

    private function oauthError(InvalidOAuth $e): void
    {
        $this->presenter->flashMessage($e->getExplainedMessage(), 'danger');
    }
}
