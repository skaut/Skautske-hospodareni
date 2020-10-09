<?php

declare(strict_types=1);

namespace Model\Payment\Mailing;

use DateTimeImmutable;

class Payment
{
    private string $name;

    private float $amount;

    private string $email;

    private DateTimeImmutable $dueDate;

    private ?int $variableSymbol;

    private ?int $constantSymbol;

    private string $note;

    public function __construct(string $name, float $amount, string $email, DateTimeImmutable $dueDate, ?int $variableSymbol, ?int $constantSymbol, string $note)
    {
        $this->name           = $name;
        $this->amount         = $amount;
        $this->email          = $email;
        $this->dueDate        = $dueDate;
        $this->variableSymbol = $variableSymbol;
        $this->constantSymbol = $constantSymbol;
        $this->note           = $note;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getAmount() : float
    {
        return $this->amount;
    }

    public function getEmail() : string
    {
        return $this->email;
    }

    public function getDueDate() : DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function getVariableSymbol() : ?int
    {
        return $this->variableSymbol;
    }

    public function getConstantSymbol() : ?int
    {
        return $this->constantSymbol;
    }

    public function getNote() : string
    {
        return $this->note;
    }
}
