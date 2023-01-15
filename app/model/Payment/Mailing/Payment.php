<?php

declare(strict_types=1);

namespace Model\Payment\Mailing;

use DateTimeImmutable;
use Model\Common\EmailAddress;

class Payment
{
    /** @param EmailAddress[] $recipients */
    public function __construct(private string $name, private float $amount, private array $recipients, private DateTimeImmutable $dueDate, private int|null $variableSymbol = null, private int|null $constantSymbol = null, private string $note)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    /** @return EmailAddress[] */
    public function getRecipients(): array
    {
        return $this->recipients;
    }

    public function getDueDate(): DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function getVariableSymbol(): int|null
    {
        return $this->variableSymbol;
    }

    public function getConstantSymbol(): int|null
    {
        return $this->constantSymbol;
    }

    public function getNote(): string
    {
        return $this->note;
    }
}
