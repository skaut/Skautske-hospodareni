<?php

declare(strict_types=1);

namespace Model\Payment\Handlers\Payment;

use Model\Payment\Commands\Payment\CreatePayment;
use Model\Payment\Payment;
use Model\Payment\Repositories\IGroupRepository;
use Model\Payment\Repositories\IPaymentRepository;

final class CreatePaymentHandler
{
    /** @var IPaymentRepository */
    private $payments;

    /** @var IGroupRepository */
    private $groups;

    public function __construct(IPaymentRepository $payments, IGroupRepository $groups)
    {
        $this->payments = $payments;
        $this->groups   = $groups;
    }

    public function __invoke(CreatePayment $command) : void
    {
        $group = $this->groups->find($command->getGroupId());

        $this->payments->save(
            new Payment(
                $group,
                $command->getName(),
                $command->getEmail(),
                $command->getAmount(),
                $command->getDueDate(),
                $command->getVariableSymbol(),
                $command->getConstantSymbol(),
                $command->getPersonId(),
                $command->getNote()
            )
        );
    }
}
