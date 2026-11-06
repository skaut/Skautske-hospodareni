<?php

declare(strict_types=1);

namespace App\Model\Payment\Mailing;

use App\Model\Payment\Repositories\IGroupRepository;
use App\Model\Payment\Repositories\IPaymentRepository;
use Cake\Chronos\ChronosDate;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

use function sprintf;

/**
 * Cron job of automatic payment reminders. It only queues them; the task queue sends them.
 *
 * A group sends them when the user switched them on, chose how many days after the due date they go
 * out, the reminder template is active, the group is open and has a sender. Each unpaid payment gets
 * the automatic reminder once, and only when no reminder was sent to it before, not even by hand.
 *
 * A failure of one group is logged and the other groups still get their reminders.
 */
final class AutomaticPaymentReminders
{
    public function __construct(
        private IGroupRepository $groups,
        private IPaymentRepository $payments,
        private PaymentEmailQueue $emailQueue,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    public function queueDue(): void
    {
        $today = ChronosDate::today();
        $queued = 0;
        $failed = 0;

        foreach ($this->groups->findSendingAutomaticReminders() as $group) {
            try {
                $payments = $this->payments->findDueAutomaticReminders((int) $group->getId(), (int) $group->getReminderDaysAfterDue(), $today);

                if ($payments === []) {
                    continue;
                }

                $queued += $this->emailQueue->queueAutomaticReminders($group, $payments)->queued;
            } catch (Throwable $exception) {
                ++$failed;
                $this->logger->error(
                    sprintf('Automatic payment reminders of group #%d could not be queued: %s', (int) $group->getId(), $exception->getMessage()),
                    ['exception' => $exception],
                );

                // A failed transaction closes the entity manager, nothing more can be saved in this request.
                // The groups left out get their reminders in the next run of the cron.
                if (! $this->entityManager->isOpen()) {
                    break;
                }
            }
        }

        $this->logger->info(sprintf('Queued %d automatic payment reminders, %d groups failed', $queued, $failed));
    }
}
