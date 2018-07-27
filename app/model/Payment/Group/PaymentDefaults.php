<?php

declare(strict_types=1);

namespace Model\Payment\Group;

use DateTimeImmutable;
use Model\Payment\DueDateIsNotWorkday;
use Model\Payment\VariableSymbol;

final class PaymentDefaults
{
    /** @var float|NULL */
    private $amount;

    /** @var DateTimeImmutable|NULL */
    private $dueDate;

    /** @var int|NULL */
    private $constantSymbol;

    /** @var VariableSymbol|NULL */
    private $nextVariableSymbol;

    /**
     * @throws DueDateIsNotWorkday
     */
    public function __construct(
        ?float $amount,
        ?DateTimeImmutable $dueDate,
        ?int $constantSymbol,
        ?VariableSymbol $nextVariableSymbol
    ) {
        if ($dueDate !== null && ! $this->isWorkday($dueDate)) {
            throw new DueDateIsNotWorkday();
        }

        $this->amount             = $amount !== 0.0 ? $amount : null;
        $this->dueDate            = $dueDate;
        $this->constantSymbol     = $constantSymbol;
        $this->nextVariableSymbol = $nextVariableSymbol;
    }

    public function getAmount() : ?float
    {
        return $this->amount;
    }

    public function getDueDate() : ?DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function getConstantSymbol() : ?int
    {
        return $this->constantSymbol;
    }

    public function getNextVariableSymbol() : ?VariableSymbol
    {
        return $this->nextVariableSymbol;
    }

    private function isWorkday(DateTimeImmutable $date) : bool
    {
        $dayOfWeek = (int) $date->format('N');

        return $dayOfWeek <= 5;
    }
}
