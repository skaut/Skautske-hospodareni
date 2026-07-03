<?php

declare(strict_types=1);

namespace App\Model\Payment\Group;

use App\Model\Payment\DueDateIsNotWorkday;
use App\Model\Payment\VariableSymbol;
use Cake\Chronos\ChronosDate;
use Doctrine\ORM\Mapping as ORM;

/** @ORM\Embeddable() */
final class PaymentDefaults
{
    /** @ORM\Column(type="float", nullable=true) */
    private ?float $amount = null;

    /** @ORM\Column(type="chronos_date", nullable=true) */
    private ?ChronosDate $dueDate = null;

    /** @ORM\Column(type="integer", nullable=true) */
    private ?int $constantSymbol = null;

    /** @ORM\Column(type="variable_symbol", nullable=true) */
    private ?VariableSymbol $nextVariableSymbol = null;

    /** @throws DueDateIsNotWorkday */
    public function __construct(
        ?float $amount,
        ?ChronosDate $dueDate,
        ?int $constantSymbol,
        ?VariableSymbol $nextVariableSymbol,
    ) {
        if ($dueDate !== null && ! $dueDate->isWeekday()) {
            throw new DueDateIsNotWorkday();
        }

        $this->amount = $amount !== 0.0 ? $amount : null;
        $this->dueDate = $dueDate;
        $this->constantSymbol = $constantSymbol;
        $this->nextVariableSymbol = $nextVariableSymbol;
    }

    public function getAmount(): ?float
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
