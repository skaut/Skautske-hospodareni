<?php

declare(strict_types=1);

namespace Model\Payment\Subscribers;

use Model\Google\Exception\OAuthNotSet;
use Model\Payment\DomainEvents\PaymentWasCompleted;
use Model\Payment\EmailTemplateNotSet;
use Model\Payment\EmailType;
use Model\Payment\MailingService;
use Model\Payment\PaymentHasNoEmails;

final class PaymentMailSubscriber
{
    public function __construct(private MailingService $mailingService)
    {
    }

    public function __invoke(PaymentWasCompleted $event): void
    {
        try {
            $this->mailingService->sendEmail($event->getId(), EmailType::get(EmailType::PAYMENT_COMPLETED));
        } catch (EmailTemplateNotSet | OAuthNotSet | PaymentHasNoEmails) {
        }
    }
}
