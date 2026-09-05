<?php

declare(strict_types=1);

namespace App\Model\Payment\Commands\Payment;

use App\Model\Common\EmailAddress;
use App\Model\Payment\VariableSymbol;
use Cake\Chronos\ChronosDate;
use Money\Money;

final class UpdatePayment
{
    /** @param EmailAddress[] $recipients */
    public function __construct(
        private int $paymentId,
        private string $name,
        private array $recipients,
        private Money $amount,
        private ChronosDate $dueDate,
        private ?VariableSymbol $variableSymbol,
        private ?int $constantSymbol,
        private string $note,
    ) {
    }

    public function getPaymentId(): int
    {
        return $this->paymentId;
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

    public function getAmount(): Money
    {
        return $this->amount;
    }

    public function getDueDate(): ChronosDate
    {
        return $this->dueDate;
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
