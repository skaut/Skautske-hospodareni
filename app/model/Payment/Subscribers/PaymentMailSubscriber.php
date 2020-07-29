<?php

declare(strict_types=1);

namespace Model\Payment\Subscribers;

use Model\Payment\DomainEvents\PaymentWasCompleted;
use Model\Payment\EmailTemplateNotSet;
use Model\Payment\EmailType;
use Model\Payment\InvalidEmail;
use Model\Payment\MailingService;

final class PaymentMailSubscriber
{
    /** @var MailingService */
    private $mailingService;

    public function __construct(MailingService $mailingService)
    {
        $this->mailingService = $mailingService;
    }

    public function __invoke(PaymentWasCompleted $event) : void
    {
        try {
            $this->mailingService->sendEmail($event->getId(), EmailType::get(EmailType::PAYMENT_COMPLETED));
        } catch (EmailTemplateNotSet | InvalidEmail $e) {
        }
    }
}
