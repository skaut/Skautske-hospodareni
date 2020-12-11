<?php

declare(strict_types=1);

namespace Model\Payment\Mailing;

use DateTimeImmutable;
use Model\Common\EmailAddress;

class Payment
{
    private string $name;

    private float $amount;

    /** @var EmailAddress[] */
    private array $recipients;

    private DateTimeImmutable $dueDate;

    private ?int $variableSymbol = null;

    private ?int $constantSymbol = null;

    private string $note;

    /**
     * @param EmailAddress[] $recipients
     */
    public function __construct(string $name, float $amount, array $recipients, DateTimeImmutable $dueDate, ?int $variableSymbol, ?int $constantSymbol, string $note)
    {
        $this->name           = $name;
        $this->amount         = $amount;
        $this->recipients     = $recipients;
        $this->dueDate        = $dueDate;
        $this->variableSymbol = $variableSymbol;
        $this->constantSymbol = $constantSymbol;
        $this->note           = $note;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * @return EmailAddress[]
     */
    public function getRecipients(): array
    {
        return $this->recipients;
    }

    public function getDueDate(): DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function getVariableSymbol(): ?int
    {
        return $this->variableSymbol;
    }

    public function getConstantSymbol(): ?int
    {
        return $this->constantSymbol;
    }

    public function getNote(): string
    {
        return $this->note;
    }
}
