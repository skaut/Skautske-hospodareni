<?php

declare(strict_types=1);

namespace Model\Payment\Commands\Payment;

use Cake\Chronos\Date;
use Model\Common\EmailAddress;
use Model\Payment\Handlers\Payment\CreatePaymentHandler;
use Model\Payment\VariableSymbol;

/** @see CreatePaymentHandler */
final class CreatePayment
{
    /** @param EmailAddress[] $recipients */
    public function __construct(
        private int $groupId,
        private string $name,
        private array $recipients,
        private float $amount,
        private Date $dueDate,
        private int|null $personId = null,
        private VariableSymbol|null $variableSymbol = null,
        private int|null $constantSymbol = null,
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

    public function getDueDate(): Date
    {
        return $this->dueDate;
    }

    public function getPersonId(): int|null
    {
        return $this->personId;
    }

    public function getVariableSymbol(): VariableSymbol|null
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
