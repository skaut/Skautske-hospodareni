<?php

declare(strict_types=1);

namespace Model\Payment\Subscribers;

use Model\Payment\DomainEvents\PaymentAmountWasChanged;
use Model\Payment\DomainEvents\PaymentVariableSymbolWasChanged;
use Model\Payment\DomainEvents\PaymentWasCreated;
use Model\Payment\Repositories\IGroupRepository;
use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;

/**
 * When payment VS is changed, there is a risk that corresponding bank transaction
 * won't be loaded on next pairing because paring mechanism only checks new payments since 'last pairing'.
 *
 * When 'last pairing' is invalidated, all bank payments will be loaded
 */
final class PaymentSubscriber implements MessageSubscriberInterface
{
    public function __construct(private IGroupRepository $groups)
    {
    }

    /** @return array<string, mixed> */
    public static function getHandledMessages(): array
    {
        return [
            PaymentWasCreated::class => ['method' => 'handlePaymentCreated'],
            PaymentVariableSymbolWasChanged::class => ['method' => 'handleVariableSymbolChanged'],
            PaymentAmountWasChanged::class => ['method' => 'handlePaymentAmountChanged'],
        ];
    }

    public function handlePaymentCreated(PaymentWasCreated $event): void
    {
        if ($event->getVariableSymbol() === null) {
            return; // no risk of unpaired payment
        }

        $this->invalidateLastPairing($event->getGroupId());
    }

    public function handleVariableSymbolChanged(PaymentVariableSymbolWasChanged $event): void
    {
        if ($event->getVariableSymbol() === null) {
            return; // no risk of unpaired payment
        }

        $this->invalidateLastPairing($event->getGroupId());
    }

    public function handlePaymentAmountChanged(PaymentAmountWasChanged $event): void
    {
        if ($event->getVariableSymbol() === null) {
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
