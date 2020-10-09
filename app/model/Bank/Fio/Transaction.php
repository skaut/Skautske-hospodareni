<?php

declare(strict_types=1);

namespace Model\Bank\Fio;

use DateTimeImmutable;
use Nette;

/**
 * @property-read string                $id
 * @property-read DateTimeImmutable $date
 * @property-read float                 $amount
 * @property-read string                $bankAccount
 * @property-read string                $name
 * @property-read int|NULL              $variableSymbol
 * @property-read int|NULL              $constantSymbol
 * @property-read string|NULL           $note
 */
class Transaction
{
    use Nette\SmartObject;

    private string $id;

    private DateTimeImmutable $date;

    private float $amount;

    private string $bankAccount;

    private string $name;

    /** @var int|NULL */
    private $variableSymbol;

    /** @var int|NULL */
    private $constantSymbol;

    /** @var string|NULL */
    private $note;

    public function __construct(
        string $id,
        DateTimeImmutable $date,
        float $amount,
        string $bankAccount,
        string $name,
        ?int $variableSymbol,
        ?int $constantSymbol,
        ?string $note
    ) {
        $this->id             = $id;
        $this->date           = $date;
        $this->amount         = $amount;
        $this->bankAccount    = $bankAccount;
        $this->name           = $name;
        $this->variableSymbol = $variableSymbol;
        $this->constantSymbol = $constantSymbol;
        $this->note           = $note;
    }

    public function getId() : string
    {
        return $this->id;
    }

    public function getDate() : DateTimeImmutable
    {
        return $this->date;
    }

    public function getAmount() : float
    {
        return $this->amount;
    }

    public function getBankAccount() : string
    {
        return $this->bankAccount;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getVariableSymbol() : ?int
    {
        return $this->variableSymbol;
    }

    public function getConstantSymbol() : ?int
    {
        return $this->constantSymbol;
    }

    public function getNote() : ?string
    {
        return $this->note;
    }
}
