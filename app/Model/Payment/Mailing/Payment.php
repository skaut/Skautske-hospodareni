<?php

declare(strict_types=1);

namespace App\Model\Payment\Mailing;

use App\Model\Common\EmailAddress;
use DateTimeImmutable;

class Payment
{
    /** @param EmailAddress[] $recipients */
    public function __construct(
        private string $name,
        private float $amount,
        private array $recipients,
        private DateTimeImmutable $dueDate,
        private ?int $variableSymbol,
        private ?int $constantSymbol,
        private string $note,
    ) {
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
