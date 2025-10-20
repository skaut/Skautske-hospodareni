<?php

declare(strict_types=1);

namespace Model\Event\Handlers\Mailing;

use Model\Google\InvalidOAuth;
use Model\Payment\Commands\Mailing\SendPaymentReminder;
use Model\Payment\EmailType;
use Model\Payment\MailingService;
use Model\Payment\PaymentClosed;
use Model\Payment\Repositories\IPaymentRepository;

final class SendPaymentReminderHandler
{
    public function __construct(private IPaymentRepository $payments, private MailingService $mailingService)
    {
    }

    /** @throws InvalidOAuth */
    public function __invoke(SendPaymentReminder $command): void
    {
        $payment = $this->payments->find($command->getPaymentId());

        if ($payment->isClosed()) {
            throw PaymentClosed::withName($payment->getName());
        }

        $this->mailingService->sendEmail($payment->getId(), EmailType::get(EmailType::PAYMENT_REMINDER), $command->isCli());
    }
}
