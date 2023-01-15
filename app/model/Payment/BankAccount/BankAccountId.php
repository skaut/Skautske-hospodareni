<?php

declare(strict_types=1);

namespace Model\Payment\BankAccount;

final class BankAccountId
{
    public function __construct(private int $id)
    {
    }

    public function toInt(): int
    {
        return $this->id;
    }
}
