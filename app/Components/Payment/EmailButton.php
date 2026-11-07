<?php

declare(strict_types=1);

namespace App\Components\Payment;

use App\Components\BaseControl;
use App\Model\Common\Services\QueryBus;
use App\Model\DTO\Payment\Group;
use App\Model\DTO\Payment\Payment;
use App\Model\Google\Exception\OAuthNotSet;
use App\Model\Google\InvalidOAuth;
use App\Model\Payment\EmailNotSet;
use App\Model\Payment\EmailType;
use App\Model\Payment\InvalidBankAccount;
use App\Model\Payment\Mailing\PaymentEmailQueue;
use App\Model\Payment\MailingService;
use App\Model\Payment\ReadModel\Queries\GroupEmailQuery;

use function array_filter;
use function array_map;

class EmailButton extends BaseControl
{
    public const NO_MAILER_MESSAGE = 'Nemáte nastavený mail pro odesílání u skupiny';
    public const NO_BANK_ACCOUNT_MESSAGE = 'Skupina nemá nastavený bankovní účet';

    public const NO_TEMPLATE_ASSIGNED = 'Skupina nemá nastavenou šablonu pro upomínku';

    /**
     * @method void                                   onSendPaymentInfoRequested(array $paymentIds)
     * @var    array<callable(array<int, int>): void>
     */
    public array $onSendPaymentInfoRequested = [];

    /** @param Payment[] $payments */
    public function __construct(private QueryBus $queryBus, private PaymentEmailQueue $emailQueue, private MailingService $mailing, private bool $isEditable, private array $payments, private Group $group)
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
        return $this->group->getOauthId() !== null;
    }

    /**
     * rozešle všechny neposlané e-maily.
     */
    public function handleSendGroup(): void
    {
        $this->onSendPaymentInfoRequested(array_map(
            static fn (Payment $payment): int => $payment->getId(),
            $this->paymentsAvailableForGroupInfoSending($this->payments),
        ));
    }

    public function handleSendGroupReminder(): void
    {
        $type = EmailType::get(EmailType::PAYMENT_REMINDER);
        $dispatch = $this->emailQueue->send(
            $this->group->id,
            $type,
            array_map(static fn (Payment $payment): int => $payment->getId(), $this->paymentsAvailableForGroupReminderSending($this->payments)),
        );

        foreach (PaymentEmailMessages::forDispatch($dispatch, $type) as [$message, $flashType]) {
            $this->getPresenter()->flashMessage($message, $flashType);
        }

        $this->getPresenter()->redirect('this');
    }

    public function handleSendTest(): void
    {
        if (! $this->isEditable) {
            $this->getPresenter()->flashMessage('Neplatný požadavek na odeslání testovacího e-mailu!', 'danger');
            $this->getPresenter()->redirect('this');
        }

        try {
            $email = $this->mailing->sendTestMail($this->group->id);
            $this->getPresenter()->flashMessage('Testovací e-mail byl odeslán na '.$email.'.');
        } catch (OAuthNotSet) {
            $this->getPresenter()->flashMessage(self::NO_MAILER_MESSAGE, 'warning');
        } catch (InvalidOAuth $e) {
            $this->oauthError($e);
        } catch (InvalidBankAccount) {
            $this->getPresenter()->flashMessage(self::NO_BANK_ACCOUNT_MESSAGE, 'warning');
        } catch (EmailNotSet) {
            $this->flashMessage('Nemáte nastavený e-mail ve skautisu, na který by se odeslal testovací e-mail!', 'danger');
        }

        $this->redirect('this');
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
        $this->getPresenter()->flashMessage($e->getExplainedMessage(), 'danger');
    }
}
