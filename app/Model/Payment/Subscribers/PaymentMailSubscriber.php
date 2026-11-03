<?php

declare(strict_types=1);

namespace App\Model\Payment\Subscribers;

use App\Model\Google\Exception\OAuthNotSet;
use App\Model\Google\InvalidOAuth;
use App\Model\Payment\DomainEvents\PaymentWasCompleted;
use App\Model\Payment\EmailTemplateNotSet;
use App\Model\Payment\EmailType;
use App\Model\Payment\InvalidBankAccount;
use App\Model\Payment\MailingService;
use App\Model\Payment\PaymentHasNoEmails;
use Psr\Log\LoggerInterface;

use function sprintf;

final class PaymentMailSubscriber
{
    public function __construct(private MailingService $mailingService, private LoggerInterface $logger)
    {
    }

    /**
     * The e-mail runs inside the transaction that completes the payment. A broken mail setup of the group
     * must not roll back the payment, otherwise one expired Google account would stop bank pairing.
     */
    public function __invoke(PaymentWasCompleted $event): void
    {
        try {
            $this->mailingService->sendEmail($event->getId(), EmailType::get(EmailType::PAYMENT_COMPLETED));
        } catch (EmailTemplateNotSet|OAuthNotSet|PaymentHasNoEmails) {
        } catch (InvalidOAuth|InvalidBankAccount $exception) {
            $this->logger->warning(
                sprintf('Payment #%d was completed without the confirmation e-mail: %s', $event->getId(), $exception->getMessage()),
                ['exception' => $exception],
            );
        }
    }
}
