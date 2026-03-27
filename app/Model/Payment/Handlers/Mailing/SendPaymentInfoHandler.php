<?php

declare(strict_types=1);

namespace App\Model\Event\Handlers\Mailing;

use App\Model\Google\InvalidOAuth;
use App\Model\Payment\Commands\Mailing\SendPaymentInfo;
use App\Model\Payment\EmailType;
use App\Model\Payment\MailingService;
use App\Model\Payment\PaymentClosed;
use App\Model\Payment\Repositories\IPaymentRepository;

final class SendPaymentInfoHandler
{
    public function __construct(private IPaymentRepository $payments, private MailingService $mailingService)
    {
    }

    /** @throws InvalidOAuth */
    public function __invoke(SendPaymentInfo $command): void
    {
        $payment = $this->payments->find($command->getPaymentId());

        if ($payment->isClosed()) {
            throw PaymentClosed::withName($payment->getName());
        }

        $this->mailingService->sendEmail($payment->getId(), EmailType::get(EmailType::PAYMENT_INFO));
    }
}
