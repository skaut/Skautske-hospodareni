<?php

declare(strict_types=1);

namespace App\Model\Payment\Mailing;

use App\Model\Payment\Repositories\IGroupRepository;
use App\Model\Payment\Repositories\IPaymentRepository;
use Cake\Chronos\ChronosDate;
use Psr\Log\LoggerInterface;

use function sprintf;

/**
 * Cron job of automatic payment reminders. It only queues them; the task queue sends them.
 *
 * A group sends them when the user switched them on, chose how many days after the due date they go
 * out, the reminder template is active, the group is open and has a sender. Each unpaid payment gets
 * the automatic reminder once, and only when no reminder was sent to it before, not even by hand.
 */
final class AutomaticPaymentReminders
{
    public function __construct(
        private IGroupRepository $groups,
        private IPaymentRepository $payments,
        private PaymentEmailQueue $emailQueue,
        private LoggerInterface $logger,
    ) {
    }

    public function queueDue(): void
    {
        $today = ChronosDate::today();
        $queued = 0;

        foreach ($this->groups->findSendingAutomaticReminders() as $group) {
            $payments = $this->payments->findDueAutomaticReminders((int) $group->getId(), (int) $group->getReminderDaysAfterDue(), $today);

            if ($payments === []) {
                continue;
            }

            $queued += $this->emailQueue->queueAutomaticReminders($group, $payments)->queued;
        }

        $this->logger->info(sprintf('Queued %d automatic payment reminders', $queued));
    }
}
