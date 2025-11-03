<?php

declare(strict_types=1);

namespace Model;

use App\AccountancyModule\PaymentModule\Components\EmailButton;
use Model\Common\Services\CommandBus;
use Model\Google\Exception\OAuthNotSet;
use Model\Google\InvalidOAuth;
use Model\Payment\Commands\Mailing\SendPaymentReminder;
use Model\Payment\EmailTemplateNotSet;
use Model\Payment\InvalidBankAccount;
use Model\Payment\PaymentHasNoEmails;
use Model\Payment\Repositories\IGroupRepository;
use Model\Payment\Repositories\IPaymentRepository;
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
