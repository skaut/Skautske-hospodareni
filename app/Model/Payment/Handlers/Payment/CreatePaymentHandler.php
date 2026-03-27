<?php

declare(strict_types=1);

namespace App\Model\Payment\Handlers\Payment;

use App\Model\Payment\Commands\Payment\CreatePayment;
use App\Model\Payment\Payment;
use App\Model\Payment\Repositories\IGroupRepository;
use App\Model\Payment\Repositories\IPaymentRepository;
use App\Model\Payment\Services\VariableSymbolCollisionChecker;

final class CreatePaymentHandler
{
    public function __construct(
        private IPaymentRepository $payments,
        private IGroupRepository $groups,
        private VariableSymbolCollisionChecker $variableSymbolCollisionChecker,
    ) {
    }

    public function __invoke(CreatePayment $command): void
    {
        $group = $this->groups->find($command->getGroupId());
        $variableSymbol = $command->getVariableSymbol();

        if ($variableSymbol !== null) {
            $this->variableSymbolCollisionChecker->assertUniqueForPayment($group, null, $variableSymbol);
        }

        $this->payments->save(
            new Payment(
                $group,
                $command->getName(),
                $command->getRecipients(),
                $command->getAmount(),
                $command->getDueDate(),
                $command->getVariableSymbol(),
                $command->getConstantSymbol(),
                $command->getPersonId(),
                $command->getNote(),
            ),
        );
    }
}
