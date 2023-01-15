<?php

declare(strict_types=1);

namespace Model\Payment\Subscribers;

use Model\Payment\DomainEvents\GroupWasRemoved;
use Model\Payment\Repositories\IPaymentRepository;

final class GroupRemovedSubscriber
{
    public function __construct(private IPaymentRepository $payments)
    {
    }

    public function __invoke(GroupWasRemoved $event): void
    {
        foreach ($this->payments->findByGroup($event->getGroupId()) as $payment) {
            $this->payments->remove($payment);
        }
    }
}
