<?php

declare(strict_types=1);

namespace Model\Payment\Commands\Payment;

use Cake\Chronos\Date;
use Model\Common\EmailAddress;
use Model\Payment\VariableSymbol;

final class UpdatePayment
{
    private string $name;

    /** @var EmailAddress[] */
    private array $recipients;

    private float $amount;

    private Date $dueDate;

    private ?VariableSymbol $variableSymbol = null;

    private ?int $constantSymbol = null;

    private string $note;

    private int $paymentId;

    /** @param EmailAddress[] $recipients */
    public function __construct(
        int $paymentId,
        string $name,
        array $recipients,
        float $amount,
        Date $dueDate,
        ?VariableSymbol $variableSymbol,
        ?int $constantSymbol,
        string $note
    ) {
        $this->paymentId      = $paymentId;
        $this->name           = $name;
        $this->recipients     = $recipients;
        $this->amount         = $amount;
        $this->dueDate        = $dueDate;
        $this->variableSymbol = $variableSymbol;
        $this->constantSymbol = $constantSymbol;
        $this->note           = $note;
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

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getDueDate(): Date
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
