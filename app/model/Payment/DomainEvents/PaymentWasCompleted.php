<?php

declare(strict_types=1);

namespace Model\Payment\DomainEvents;

final class PaymentWasCompleted
{
    public function __construct(private int $id)
    {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
