<?php

declare(strict_types=1);

namespace Model\Payment\Subscribers;


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
        if($event->getVariableSymbol() === NULL) {
            return;
        }

        $group = $this->groups->find($event->getGroupId());
        $group->invalidateLastPairing();
        $this->groups->save($group);
    }

}
