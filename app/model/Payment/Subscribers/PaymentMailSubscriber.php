<?php

declare(strict_types=1);

namespace Model\Payment\Subscribers;

use Model\Common\Services\NotificationsCollector;
use Model\Payment\DomainEvents\PaymentWasCompleted;
use Model\Payment\EmailTemplateNotSet;
use Model\Payment\EmailType;
use Model\Payment\InvalidEmail;
use Model\Payment\InvalidSmtp;
use Model\Payment\MailCredentialsNotSet;
use Model\Payment\MailingService;

final class PaymentMailSubscriber
{
    /** @var MailingService */
    private $mailingService;

    /** @var NotificationsCollector */
    private $notificationsCollector;

    public function __construct(MailingService $mailingService, NotificationsCollector $notificationsCollector)
    {
        $this->mailingService         = $mailingService;
        $this->notificationsCollector = $notificationsCollector;
    }

    public function __invoke(PaymentWasCompleted $event) : void
    {
        try {
            $this->mailingService->sendEmail($event->getId(), EmailType::get(EmailType::PAYMENT_COMPLETED));
        } catch (InvalidSmtp $e) {
            $this->notificationsCollector->error(
                'Email při dokončení platby nemohl být odeslán. Chyba SMTP serveru: ' . $e->getMessage()
            );
        } catch (EmailTemplateNotSet | MailCredentialsNotSet | InvalidEmail $e) {
        }
    }
}
