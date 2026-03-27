<?php

declare(strict_types=1);

namespace App\Model\Payment\Subscribers;

use App\Model\Google\Exception\OAuthNotSet;
use App\Model\Payment\DomainEvents\PaymentWasCompleted;
use App\Model\Payment\EmailTemplateNotSet;
use App\Model\Payment\EmailType;
use App\Model\Payment\MailingService;
use App\Model\Payment\PaymentHasNoEmails;

final class PaymentMailSubscriber
{
    public function __construct(private MailingService $mailingService)
    {
    }

    public function __invoke(PaymentWasCompleted $event): void
    {
        try {
            $this->mailingService->sendEmail($event->getId(), EmailType::get(EmailType::PAYMENT_COMPLETED));
        } catch (EmailTemplateNotSet|OAuthNotSet|PaymentHasNoEmails) {
        }
    }
}
