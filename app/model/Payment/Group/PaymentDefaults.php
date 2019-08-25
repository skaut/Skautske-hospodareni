<?php

declare(strict_types=1);

namespace Model\Payment\Group;

use Cake\Chronos\Date;
use Doctrine\ORM\Mapping as ORM;
use Model\Payment\DueDateIsNotWorkday;
use Model\Payment\VariableSymbol;

/**
 * @ORM\Embeddable()
 */
final class PaymentDefaults
{
    /**
     * @ORM\Column(type="float", nullable=true)
     *
     * @var float|NULL
     */
    private $amount;

    /**
     * @ORM\Column(type="chronos_date", nullable=true, name="maturity")
     *
     * @var Date|NULL
     */
    private $dueDate;

    /**
     * @ORM\Column(type="integer", nullable=true, name="ks", options={"unsigned"=true})
     *
     * @var int|NULL
     */
    private $constantSymbol;

    /**
     * @ORM\Column(type="variable_symbol", nullable=true, name="nextVs")
     *
     * @var VariableSymbol|NULL
     */
    private $nextVariableSymbol;

    /**
     * @throws DueDateIsNotWorkday
     */
    public function __construct(
        ?float $amount,
        ?Date $dueDate,
        ?int $constantSymbol,
        ?VariableSymbol $nextVariableSymbol
    ) {
        if ($dueDate !== null && ! $dueDate->isWeekday()) {
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

    public function getDueDate() : ?Date
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
}
