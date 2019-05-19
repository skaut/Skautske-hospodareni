<?php

declare(strict_types=1);

namespace Model\Payment\Handlers\Payment;

use Model\Payment\Commands\Payment\UpdatePayment;
use Model\Payment\Repositories\IPaymentRepository;

final class UpdatePaymentHandler
{
    /** @var IPaymentRepository */
    private $payments;

    public function __construct(IPaymentRepository $payments)
    {
        $this->payments = $payments;
    }

    public function __invoke(UpdatePayment $command) : void
    {
        $payment = $this->payments->find($command->getPaymentId());

        $payment->update(
            $command->getName(),
            $command->getEmail(),
            $command->getAmount(),
            $command->getDueDate(),
            $command->getVariableSymbol(),
            $command->getConstantSymbol(),
            $command->getNote()
        );

        $this->payments->save($payment);
    }
}
