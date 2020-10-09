<?php

declare(strict_types=1);

namespace Model\Payment\Subscribers;

use Model\Payment\DomainEvents\PaymentAmountWasChanged;
use Model\Payment\DomainEvents\PaymentVariableSymbolWasChanged;
use Model\Payment\DomainEvents\PaymentWasCreated;
use Model\Payment\Repositories\IGroupRepository;

/**
 * When payment VS is changed, there is a risk that corresponding bank transaction
 * won't be loaded on next pairing because paring mechanism only checks new payments since 'last pairing'.
 *
 * When 'last pairing' is invalidated, all bank payments will be loaded
 */
class PaymentSubscriber
{
    private IGroupRepository $groups;

    public function __construct(IGroupRepository $groups)
    {
        $this->groups = $groups;
    }

    public function handlePaymentCreated(PaymentWasCreated $event) : void
    {
        if ($event->getVariableSymbol() === null) {
            return; // no risk of unpaired payment
        }

        $this->invalidateLastPairing($event->getGroupId());
    }

    public function handleVariableSymbolChanged(PaymentVariableSymbolWasChanged $event) : void
    {
        if ($event->getVariableSymbol() === null) {
            return; // no risk of unpaired payment
        }

        $this->invalidateLastPairing($event->getGroupId());
    }

    public function handlePaymentAmountChanged(PaymentAmountWasChanged $event) : void
    {
        if ($event->getVariableSymbol() === null) {
            return; // no risk of unpaired payment
        }

        $this->invalidateLastPairing($event->getGroupId());
    }

    private function invalidateLastPairing(int $groupId) : void
    {
        $group = $this->groups->find($groupId);
        $group->invalidateLastPairing();
        $this->groups->save($group);
    }
}
