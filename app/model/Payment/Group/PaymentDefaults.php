<?php

declare(strict_types=1);

namespace Model\Payment\Group;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Model\Payment\DueDateIsNotWorkday;
use Model\Payment\VariableSymbol;

/**
 * @ORM\Embeddable()
 */
final class PaymentDefaults
{
    /**
     * @var float|NULL
     * @ORM\Column(type="float", nullable=true)
     */
    private $amount;

    /**
     * @var DateTimeImmutable|NULL
     * @ORM\Column(type="datetime_immutable", nullable=true, name="maturity")
     */
    private $dueDate;

    /**
     * @var int|NULL
     * @ORM\Column(type="integer", nullable=true, name="ks")
     */
    private $constantSymbol;

    /**
     * @var VariableSymbol|NULL
     * @ORM\Column(type="variable_symbol", nullable=true, name="nextVs")
     */
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
