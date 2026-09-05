<?php

declare(strict_types=1);

namespace App\Model\Payment\Group;

use App\Model\Payment\DueDateIsNotWorkday;
use App\Model\Payment\VariableSymbol;
use Cake\Chronos\ChronosDate;
use Doctrine\ORM\Mapping as ORM;
use Money\Money;

/** @ORM\Embeddable() */
final class PaymentDefaults
{
    /** @ORM\Column(type="money", nullable=true) */
    private ?Money $amount = null;

    /** @ORM\Column(type="chronos_date", nullable=true) */
    private ?ChronosDate $dueDate = null;

    /** @ORM\Column(type="integer", nullable=true) */
    private ?int $constantSymbol = null;

    /** @ORM\Column(type="variable_symbol", nullable=true) */
    private ?VariableSymbol $nextVariableSymbol = null;

    /** @throws DueDateIsNotWorkday */
    public function __construct(
        ?Money $amount,
        ?ChronosDate $dueDate,
        ?int $constantSymbol,
        ?VariableSymbol $nextVariableSymbol,
    ) {
        if ($dueDate !== null && ! $dueDate->isWeekday()) {
            throw new DueDateIsNotWorkday();
        }

        $this->amount = $amount !== null && ! $amount->isZero() ? $amount : null;
        $this->dueDate = $dueDate;
        $this->constantSymbol = $constantSymbol;
        $this->nextVariableSymbol = $nextVariableSymbol;
    }

    public function getAmount(): ?Money
    {
        return $this->amount;
    }

    public function getDueDate(): ?ChronosDate
    {
        return $this->dueDate;
    }

    public function getConstantSymbol(): ?int
    {
        return $this->constantSymbol;
    }

    public function getNextVariableSymbol(): ?VariableSymbol
    {
        return $this->nextVariableSymbol;
    }
}
