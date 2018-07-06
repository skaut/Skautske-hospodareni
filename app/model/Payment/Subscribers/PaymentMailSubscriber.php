<?php

declare(strict_types=1);

namespace Model\Payment\Subscribers;

use Model\Payment\DomainEvents\PaymentWasCompleted;
use Model\Payment\EmailTemplateNotSetException;
use Model\Payment\EmailType;
use Model\Payment\InvalidEmailException;
use Model\Payment\MailCredentialsNotSetException;
use Model\Payment\MailingService;

final class PaymentMailSubscriber
{
    /** @var MailingService */
    private $mailingService;

    public function __construct(MailingService $mailingService)
    {
        $this->mailingService = $mailingService;
    }

    public function handle(PaymentWasCompleted $event) : void
    {
        try {
            $this->mailingService->sendEmail($event->getId(), EmailType::get(EmailType::PAYMENT_COMPLETED));
        } catch (EmailTemplateNotSetException | MailCredentialsNotSetException | InvalidEmailException $e) {
        }
    }
}
