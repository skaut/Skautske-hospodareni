<?php

declare(strict_types=1);

namespace Model\Payment\Group;

use Cake\Chronos\ChronosDate;
use Doctrine\ORM\Mapping as ORM;
use Model\Payment\DueDateIsNotWorkday;
use Model\Payment\VariableSymbol;

/** @ORM\Embeddable() */
final class PaymentDefaults
{
    /** @ORM\Column(type="float", nullable=true) */
    private float|null $amount = null;

    /** @ORM\Column(type="chronos_date", nullable=true) */
    private ChronosDate|null $dueDate = null;

    /** @ORM\Column(type="integer", nullable=true) */
    private int|null $constantSymbol = null;

    /** @ORM\Column(type="variable_symbol", nullable=true) */
    private VariableSymbol|null $nextVariableSymbol = null;

    /** @throws DueDateIsNotWorkday */
    public function __construct(
        float|null          $amount,
        ChronosDate|null    $dueDate,
        int|null            $constantSymbol,
        VariableSymbol|null $nextVariableSymbol,
    ) {
        if ($dueDate !== null && ! $dueDate->isWeekday()) {
            throw new DueDateIsNotWorkday();
        }

        $this->amount             = $amount !== 0.0 ? $amount : null;
        $this->dueDate            = $dueDate;
        $this->constantSymbol     = $constantSymbol;
        $this->nextVariableSymbol = $nextVariableSymbol;
    }

    public function getAmount(): float|null
    {
        return $this->amount;
    }

    public function getDueDate(): ChronosDate|null
    {
        return $this->dueDate;
    }

    public function getConstantSymbol(): int|null
    {
        return $this->constantSymbol;
    }

    public function getNextVariableSymbol(): VariableSymbol|null
    {
        return $this->nextVariableSymbol;
    }
}
