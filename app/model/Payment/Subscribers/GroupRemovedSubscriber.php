<?php

declare(strict_types=1);

namespace Model\Payment\Subscribers;

use Model\Payment\DomainEvents\GroupWasRemoved;
use Model\Payment\Repositories\IPaymentRepository;

final class GroupRemovedSubscriber
{
    /** @var IPaymentRepository */
    private $payments;

    public function __construct(IPaymentRepository $payments)
    {
        $this->payments = $payments;
    }

    public function __invoke(GroupWasRemoved $event) : void
    {
        foreach ($this->payments->findByGroup($event->getGroupId()) as $payment) {
            $this->payments->remove($payment);
        }
    }
}
