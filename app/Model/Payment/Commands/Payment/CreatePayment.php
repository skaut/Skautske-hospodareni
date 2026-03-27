<?php

declare(strict_types=1);

namespace App\Model\Payment\Commands\Payment;

use App\Model\Common\EmailAddress;
use App\Model\Payment\Handlers\Payment\CreatePaymentHandler;
use App\Model\Payment\VariableSymbol;
use Cake\Chronos\ChronosDate;

/** @see CreatePaymentHandler */
final class CreatePayment
{
    /** @param EmailAddress[] $recipients */
    public function __construct(
        private int $groupId,
        private string $name,
        private array $recipients,
        private float $amount,
        private ChronosDate $dueDate,
        private ?int $personId,
        private ?VariableSymbol $variableSymbol,
        private ?int $constantSymbol,
        private string $note,
    ) {
    }

    public function getGroupId(): int
    {
        return $this->groupId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /** @return EmailAddress[] */
    public function getRecipients(): array
    {
        return $this->recipients;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getDueDate(): ChronosDate
    {
        return $this->dueDate;
    }

    public function getPersonId(): ?int
    {
        return $this->personId;
    }

    public function getVariableSymbol(): ?VariableSymbol
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
