<?php

declare(strict_types=1);

namespace App\Model\Payment\Handlers\Payment;

use App\Model\Payment\Commands\Payment\UpdatePaymentEmailRecipients;
use App\Model\Payment\Repositories\IPaymentRepository;

final class UpdatePaymentEmailRecipientsHandler
{
    public function __construct(private IPaymentRepository $payments)
    {
    }

    public function __invoke(UpdatePaymentEmailRecipients $command): void
    {
        $payment = $this->payments->find($command->getPaymentId());
        $payment->updateEmailRecipients($command->getRecipients());
        $this->payments->save($payment);
    }
}
