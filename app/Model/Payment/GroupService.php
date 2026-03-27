<?php

declare(strict_types=1);

namespace App\Model\Payment;

use App\Components\Payment\EmailButton;
use App\Model\Common\Services\CommandBus;
use App\Model\Google\Exception\OAuthNotSet;
use App\Model\Google\InvalidOAuth;
use App\Model\Payment\Commands\Mailing\SendPaymentReminder;
use App\Model\Payment\Repositories\IGroupRepository;
use App\Model\Payment\Repositories\IPaymentRepository;
use Psr\Log\LoggerInterface;

use function array_map;

class GroupService
{
    public function __construct(
        private IGroupRepository $groups,
        private IPaymentRepository $payments,
        private CommandBus $commandBus,
        private LoggerInterface $logger,
    ) {
    }

    public function reminder(): void
    {
        $count = 0;
        $reminderGroups = $this->groups->findByReminder();
        $reminderPayments = $this->payments->findByReminder(array_map(function ($group) {
            return (int) $group->getId();
        }, $reminderGroups));

        foreach ($reminderPayments as $payment) {
            try {
                $this->commandBus->handle(new SendPaymentReminder($payment->getId(), true));
                ++$count;
            } catch (OAuthNotSet) {
                $this->logger->error('OAuth not set');
            } catch (InvalidBankAccount) {
                $this->logger->error(EmailButton::NO_BANK_ACCOUNT_MESSAGE);
            } catch (InvalidOAuth) {
                $this->logger->error('Invalid OAuth');
            } catch (EmailTemplateNotSet) {
                $this->logger->error(EmailButton::NO_TEMPLATE_ASSIGNED);
            } catch (PaymentHasNoEmails) {
                $this->logger->error('Payment has no emails');
            }
        }

        $this->logger->info('Sent reminders for '.$count.' payments');
    }
}
