<?php

declare(strict_types=1);

namespace App\Model\Payment\Mailing;

use App\Model\Common\Repositories\IUserRepository;
use App\Model\Payment\EmailType;
use App\Model\Payment\Group;
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

use function array_unique;
use function count;

/**
 * The only way e-mails of payments are sent. One e-mail sent by hand goes out right away, more e-mails
 * wait in the task queue, which the cron works through. Either way the e-mail goes through a queued
 * task, so the same e-mail of a payment cannot be sent twice at the same time.
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
    ) {
    }

    /** @param int[] $paymentIds payments of other groups are left out */
    public function send(int $groupId, EmailType $type, array $paymentIds): PaymentEmailDispatch
    {
        $tasks = [];

        foreach (array_unique($paymentIds) as $paymentId) {
            $payment = $this->findPayment((int) $paymentId);

            if ($payment !== null && $payment->getGroupId() === $groupId) {
                $tasks[] = $this->task(new PaymentEmailTask($payment->getId(), $type), $payment);
            }
        }

        $senderName = $this->users->findCurrentUser()?->getName();
        $scope = TaskScope::paymentGroup($groupId);
        $result = $this->queue->enqueue(PaymentEmailTaskHandler::TYPE, $scope, self::label($type), $senderName, $tasks);

        if ($result->jobId !== null && count($tasks) === 1) {
            foreach ($this->queue->runNow($result->jobId, $scope, $senderName) as $outcome) {
                return PaymentEmailDispatch::sentNow($outcome);
            }
        }

        return PaymentEmailDispatch::queued($result);
    }

    /**
     * Queues the confirmation e-mail of a completed payment. It runs in the transaction that completes
     * the payment, so the e-mail is queued exactly when the payment is saved as paid. Confirmations of
     * one group in one request (e.g. bank pairing) share one batch.
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
            $this->users->findCurrentUser()?->getName(),
            [$task],
        );

        if ($result->jobId === null) {
            return;
        }

        $this->completedEmailJobs[$payment->getGroupId()] = $result->jobId;
    }

    /**
     * Marks the payments and queues their automatic reminder in one transaction, so each payment gets it
     * exactly once.
     *
     * @param Payment[] $payments
     */
    public function queueAutomaticReminders(Group $group, array $payments): EnqueueResult
    {
        return $this->entityManager->wrapInTransaction(function () use ($group, $payments): EnqueueResult {
            $now = new DateTimeImmutable();
            $tasks = [];

            foreach ($payments as $payment) {
                $payment->markAutomaticReminderQueued($now);
                $this->payments->save($payment);
                $tasks[] = $this->task(new PaymentEmailTask($payment->getId(), EmailType::get(EmailType::PAYMENT_REMINDER), automatic: true), $payment);
            }

            return $this->queue->enqueue(
                PaymentEmailTaskHandler::TYPE,
                TaskScope::paymentGroup((int) $group->getId()),
                self::LABEL_AUTOMATIC_REMINDERS,
                null,
                $tasks,
            );
        });
    }

    /** Whether the e-mail of the payment already waits in the queue. */
    public function isQueued(int $paymentId, EmailType $type): bool
    {
        return $this->queue->isQueued((new PaymentEmailTask($paymentId, $type))->dedupKey());
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
}
