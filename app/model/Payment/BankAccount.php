<?php

namespace Model\Payment;


class BankAccount
{

    /** @var string */
    private $number;

    /** @var bool */
    private $main;

    public function __construct(string $number, bool $main)
    {
        $this->number = $number;
        $this->main = $main;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function isMain(): bool
    {
        return $this->main;
    }

}
