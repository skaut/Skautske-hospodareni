<?php

declare(strict_types=1);

namespace Model;

use Exception;

class BankWrongTokenAccount extends Exception
{
    public function __construct(
        private string $intendedAccount,
        private string $tokenAccount,
    ) {
    }

    public function getIntendedAccount(): string
    {
        return $this->intendedAccount;
    }

    public function getTokenAccount(): string
    {
        return $this->tokenAccount;
    }
}
