<?php

declare(strict_types=1);

namespace App\Model\Payment\Subscribers;

use App\Model\Payment\DomainEvents\GroupWasRemoved;
use App\Model\Payment\Repositories\IPaymentRepository;

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
