<?php

declare(strict_types=1);

namespace App\Model\Handlers\Payment;

use App\Model\Commands\Payment\UpdateNote;
use App\Model\Payment\Repositories\IPaymentRepository;

final class UpdateNoteHandler
{
    public function __construct(private IPaymentRepository $payments)
    {
    }

    public function __invoke(UpdateNote $command): void
    {
        $payment = $this->payments->find($command->getPaymentId());

        $payment->updateNote(
            $command->getNote(),
        );
        $this->payments->save($payment);
    }
}
