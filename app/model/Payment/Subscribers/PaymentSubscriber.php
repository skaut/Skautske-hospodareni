<?php

declare(strict_types=1);

namespace Model\Payment\Subscribers;


use Model\Payment\DomainEvents\PaymentVariableSymbolWasChanged;
use Model\Payment\DomainEvents\PaymentWasCreated;
use Model\Payment\Repositories\IGroupRepository;

class PaymentSubscriber
{

    /** @var IGroupRepository */
    private $groups;


    public function __construct(IGroupRepository $groups)
    {
        $this->groups = $groups;
    }

    public function handlePaymentCreated(PaymentWasCreated $event): void
    {
        if ($event->getVariableSymbol() === NULL) {
            return; // no risk of unpaired payment
        }

        $this->invalidateLastPairing($event->getGroupId());
    }

    public function handleVariableSymbolChanged(PaymentVariableSymbolWasChanged $event): void
    {
        if ($event->getVariableSymbol() === NULL) {
            return; // no risk of unpaired payment
        }

        $this->invalidateLastPairing($event->getGroupId());
    }

    private function invalidateLastPairing(int $groupId): void
    {
        $group = $this->groups->find($groupId);
        $group->invalidateLastPairing();
        $this->groups->save($group);
    }

}
