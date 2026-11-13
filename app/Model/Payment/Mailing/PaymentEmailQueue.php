<?php

declare(strict_types=1);

namespace App\Model\Payment\Mailing;

use App\Model\Common\Repositories\IUserRepository;
use App\Model\Common\UserNotFound;
use App\Model\Google\Entity\GoogleOAuth;
use App\Model\Google\Exception\OAuthNotFound;
use App\Model\Mail\Repositories\IGoogleRepository;
use App\Model\Payment\EmailType;
use App\Model\Payment\Group;
use App\Model\Payment\GroupNotFound;
use App\Model\Payment\Payment;
use App\Model\Payment\PaymentNotFound;
use App\Model\Payment\Repositories\IGroupRepository;
use App\Model\Payment\Repositories\IPaymentRepository;
use App\Model\TaskQueue\EnqueueResult;
use App\Model\TaskQueue\NewTask;
use App\Model\TaskQueue\TaskQueue;
use App\Model\TaskQueue\TaskScope;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Skautis\Exception as SkautisException;

use function array_filter;
use function array_map;
use function array_unique;
use function array_values;
use function sprintf;

/**
 * The only way e-mails of payments are sent: every e-mail waits in the task queue, which the cron works
 * through. A queued e-mail cannot be queued again until it finishes, so the same e-mail of a payment
 * cannot be sent twice at the same time.
 */
final class PaymentEmailQueue
{
    public const LABEL_AUTOMATIC_REMINDERS = 'Automatické upomínky';

    /** @var array<int, int> batch of confirmation e-mails started in this request, by group id */
    private array $completedEmailJobs = [];

    public function __construct(
        private TaskQueue $queue,
        private IPaymentRepository $payments,
        private IGroupRepository $groups,
        private IUserRepository $users,
        private EntityManagerInterface $entityManager,
        private IGoogleRepository $googleAccounts,
    ) {
    }

    /**
     * Queues the e-mails. Nothing that would surely fail gets into the queue: no e-mail of a group without
     * a sender, with a sender Google refuses or with the template switched off, and no e-mail of a payment
     * without a recipient, of a closed payment, or a reminder of a payment that cannot get one now. Payments
     * of other groups are left out.
     *
     * @param int[] $paymentIds
     */
    public function send(int $groupId, EmailType $type, array $paymentIds): PaymentEmailDispatch
    {
        $blocked = $this->groupBlocks($groupId, $type);

        if ($blocked !== null) {
            return PaymentEmailDispatch::blocked($blocked);
        }

        $tasks = [];
        $leftOut = [];

        foreach (array_unique($paymentIds) as $paymentId) {
            $payment = $this->findPayment((int) $paymentId);

            if ($payment === null || $payment->getGroupId() !== $groupId) {
                continue;
            }

            $reason = $this->leftOutReason($payment, $type);

            if ($reason !== null) {
                $leftOut[$reason] = ($leftOut[$reason] ?? 0) + 1;

                continue;
            }

            $tasks[] = $this->task(new PaymentEmailTask($payment->getId(), $type), $payment);
        }

        $result = $this->queue->enqueue(
            PaymentEmailTaskHandler::TYPE,
            TaskScope::paymentGroup($groupId),
            self::label($type),
            $this->users->findCurrentUser()?->getName(),
            $tasks,
        );

        return PaymentEmailDispatch::fromResult($result, $leftOut);
    }

    /**
     * Queues the confirmation e-mail of a completed payment. It runs in the transaction that completes
     * the payment, so the e-mail is queued exactly when the payment is saved as paid. Confirmations of
     * one group in one request (e.g. bank pairing) share one batch. Unlike other e-mails, a confirmation is
     * queued even when Google refuses the sender: it waits in the queue until the account is connected again.
     */
    public function queueCompletedEmail(int $paymentId): void
    {
        $payment = $this->findPayment($paymentId);

        if ($payment === null) {
            return;
        }

        $group = $this->groups->find($payment->getGroupId());
        $type = EmailType::get(EmailType::PAYMENT_COMPLETED);

        // A group without the confirmation never sent it, so nothing is queued.
        if (! $group->isEmailEnabled($type) || $group->getOauthId() === null || $payment->getEmailRecipients() === []) {
            return;
        }

        $task = $this->task(new PaymentEmailTask($paymentId, $type), $payment);
        $jobId = $this->completedEmailJobs[$payment->getGroupId()] ?? null;

        if ($jobId !== null && $this->queue->addToJob($jobId, $task)) {
            return;
        }

        $result = $this->queue->enqueue(
            PaymentEmailTaskHandler::TYPE,
            TaskScope::paymentGroup($payment->getGroupId()),
            self::label($type),
            $this->findCompletingUserName(),
            [$task],
        );

        if ($result->jobId === null) {
            return;
        }

        $this->completedEmailJobs[$payment->getGroupId()] = $result->jobId;
    }

    /**
     * Queues the automatic reminders and marks the payments in one transaction, so each payment gets it
     * exactly once. Only a payment whose reminder really got into the queue is marked: when a reminder sent
     * by hand already waits, the automatic one is left out as the same e-mail and the payment is decided
     * again on a later day (no automatic reminder when the one sent by hand went out). Nothing is queued
     * while Google refuses the sender, the reminders go out on the first day after it is connected again.
     *
     * @param Payment[] $payments
     */
    public function queueAutomaticReminders(Group $group, array $payments): EnqueueResult
    {
        if ($this->findInvalidSender($group) !== null) {
            return new EnqueueResult(null, 0, 0);
        }

        return $this->entityManager->wrapInTransaction(function () use ($group, $payments): EnqueueResult {
            $type = EmailType::get(EmailType::PAYMENT_REMINDER);
            $payments = array_values(array_filter($payments, static fn (Payment $payment): bool => $payment->getEmailRecipients() !== []));
            $tasks = array_map(
                fn (Payment $payment): NewTask => $this->task(new PaymentEmailTask($payment->getId(), $type, automatic: true), $payment),
                $payments,
            );

            $result = $this->queue->enqueue(
                PaymentEmailTaskHandler::TYPE,
                TaskScope::paymentGroup((int) $group->getId()),
                self::LABEL_AUTOMATIC_REMINDERS,
                null,
                $tasks,
            );

            $now = new DateTimeImmutable();

            foreach ($result->addedIndexes as $index) {
                $payments[$index]->markAutomaticReminderQueued($now);
                $this->payments->save($payments[$index]);
            }

            return $result;
        });
    }

    /**
     * Confirmations of the group that wait until its sender works again go out right away.
     *
     * @return int the released e-mails
     */
    public function releaseWaitingForSender(int $groupId): int
    {
        return $this->queue->releasePostponed(
            PaymentEmailTaskHandler::TYPE,
            TaskScope::paymentGroup($groupId),
            PaymentEmailTaskHandler::WAITING_FOR_SENDER_MESSAGE,
        );
    }

    public static function label(EmailType $type): string
    {
        return match ($type->getValue()) {
            EmailType::PAYMENT_INFO => 'Platební údaje',
            EmailType::PAYMENT_REMINDER => 'Upomínky',
            EmailType::PAYMENT_COMPLETED => 'Potvrzení o úhradě',
            default => 'E-maily',
        };
    }

    /**
     * Who completed the payment, only for the record of the e-mail. The confirmation is queued in the transaction
     * that completes the payment, so skautIS failing to tell must not roll it back; the record then says AUTOMAT.
     */
    private function findCompletingUserName(): ?string
    {
        try {
            return $this->users->findCurrentUser()?->getName();
        } catch (UserNotFound|SkautisException) {
            return null;
        }
    }

    /** Why no e-mail of this type of the group can go out at all, or null when it can. */
    private function groupBlocks(int $groupId, EmailType $type): ?string
    {
        try {
            $group = $this->groups->find($groupId);
        } catch (GroupNotFound) {
            return 'Platební skupina neexistuje.';
        }

        if ($group->getOauthId() === null) {
            return 'Skupina nemá nastavený e-mail odesílatele. Nastavte ho v úpravě skupiny.';
        }

        $invalidSender = $this->findInvalidSender($group);

        if ($invalidSender !== null) {
            return sprintf(
                'E-maily nejde odeslat: propojení účtu %s s Gmailem přestalo platit. Propojte účet znovu v Nastavení → E-maily (tlačítko „Propojit účet“, přihlaste se stejným účtem).',
                $invalidSender->getEmail(),
            );
        }

        if (! $group->isEmailEnabled($type)) {
            return 'Skupina nemá zapnutou šablonu tohoto e-mailu. Zapněte ji v úpravě skupiny.';
        }

        return null;
    }

    private function leftOutReason(Payment $payment, EmailType $type): ?string
    {
        if ($payment->getEmailRecipients() === []) {
            return PaymentEmailDispatch::NO_RECIPIENT;
        }

        if ($type->equalsValue(EmailType::PAYMENT_REMINDER)) {
            return $payment->canSendReminder() ? null : PaymentEmailDispatch::REMINDER_NOT_ALLOWED;
        }

        return $payment->isClosed() ? PaymentEmailDispatch::PAYMENT_CLOSED : null;
    }

    private function task(PaymentEmailTask $task, Payment $payment): NewTask
    {
        return new NewTask($task->toPayload(), $payment->getName(), $task->dedupKey());
    }

    private function findPayment(int $paymentId): ?Payment
    {
        try {
            return $this->payments->find($paymentId);
        } catch (PaymentNotFound) {
            return null;
        }
    }

    /** The sender of the group when Google refuses it; a missing one is reported by the task itself. */
    private function findInvalidSender(Group $group): ?GoogleOAuth
    {
        $oAuthId = $group->getOauthId();

        if ($oAuthId === null) {
            return null;
        }

        try {
            $sender = $this->googleAccounts->find($oAuthId);
        } catch (OAuthNotFound) {
            return null;
        }

        return $sender->isInvalid() ? $sender : null;
    }
}
