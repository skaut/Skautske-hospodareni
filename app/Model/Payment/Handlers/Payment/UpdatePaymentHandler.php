<?php

declare(strict_types=1);

namespace App\Model\Payment\Handlers\Payment;

use App\Model\Payment\Commands\Payment\UpdatePayment;
use App\Model\Payment\Repositories\IGroupRepository;
use App\Model\Payment\Repositories\IPaymentRepository;
use App\Model\Payment\Services\VariableSymbolCollisionChecker;

final class UpdatePaymentHandler
{
    public function __construct(
        private IPaymentRepository $payments,
        private IGroupRepository $groups,
        private VariableSymbolCollisionChecker $variableSymbolCollisionChecker,
    ) {
    }

    public function __invoke(UpdatePayment $command): void
    {
        $payment = $this->payments->find($command->getPaymentId());
        $variableSymbol = $command->getVariableSymbol();

        if ($variableSymbol !== null) {
            $group = $this->groups->find($payment->getGroupId());
            $this->variableSymbolCollisionChecker->assertUniqueForPayment($group, $payment->getId(), $variableSymbol);
        }

        $payment->update(
            $command->getName(),
            $command->getRecipients(),
            $command->getAmount(),
            $command->getDueDate(),
            $command->getVariableSymbol(),
            $command->getConstantSymbol(),
            $command->getNote(),
        );
        $this->payments->save($payment);
    }
}
