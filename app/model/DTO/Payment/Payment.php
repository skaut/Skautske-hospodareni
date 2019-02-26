<?php

declare(strict_types=1);

namespace Model\DTO\Payment;

use Cake\Chronos\Date;
use DateTimeImmutable;
use Model\Payment\Payment\State;
use Model\Payment\Payment\Transaction;
use Model\Payment\VariableSymbol;
use Nette\SmartObject;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read float $amount
 * @property-read string|NULL $email
 * @property-read Date $dueDate
 * @property-read VariableSymbol|NULL $variableSymbol
 * @property-read int|NULL $constantSymbol
 * @property-read string $note
 * @property-read bool $closed
 * @property-read State $state
 * @property-read Transaction $transaction
 * @property-read DateTimeImmutable|NULL $closedAt
 * @property-read int|NULL $personId
 * @property-read int $groupId
 */
class Payment
{
    use SmartObject;

    /** @var int */
    private $id;

    /** @var string */
    private $name;

    /** @var float */
    private $amount;

    /** @var string|NULL */
    private $email;

    /** @var Date */
    private $dueDate;

    /** @var VariableSymbol|NULL */
    private $variableSymbol;

    /** @var int|NULL */
    private $constantSymbol;

    /** @var string */
    private $note;

    /** @var bool */
    private $closed;

    /** @var State */
    private $state;

    /** @var Transaction|NULL */
    private $transaction;

    /** @var DateTimeImmutable|NULL */
    private $closedAt;

    /** @var int|NULL */
    private $personId;

    /** @var int */
    private $groupId;

    public function __construct(
        int $id,
        string $name,
        float $amount,
        ?string $email,
        Date $dueDate,
        ?VariableSymbol $variableSymbol,
        ?int $constantSymbol,
        string $note,
        bool $closed,
        State $state,
        ?Transaction $transaction,
        ?DateTimeImmutable $closedAt,
        ?int $personId,
        int $groupId
    ) {
        $this->id             = $id;
        $this->name           = $name;
        $this->amount         = $amount;
        $this->email          = $email;
        $this->dueDate        = $dueDate;
        $this->variableSymbol = $variableSymbol;
        $this->constantSymbol = $constantSymbol;
        $this->note           = $note;
        $this->closed         = $closed;
        $this->state          = $state;
        $this->transaction    = $transaction;
        $this->closedAt       = $closedAt;
        $this->personId       = $personId;
        $this->groupId        = $groupId;
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getAmount() : float
    {
        return $this->amount;
    }

    public function getEmail() : ?string
    {
        return $this->email;
    }

    public function getDueDate() : Date
    {
        return $this->dueDate;
    }

    public function getVariableSymbol() : ?VariableSymbol
    {
        return $this->variableSymbol;
    }

    public function getConstantSymbol() : ?int
    {
        return $this->constantSymbol;
    }

    public function getNote() : string
    {
        return $this->note;
    }

    public function isClosed() : bool
    {
        return $this->closed;
    }

    public function getState() : State
    {
        return $this->state;
    }

    public function getTransaction() : ?Transaction
    {
        return $this->transaction;
    }

    public function getClosedAt() : ?DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function getPersonId() : ?int
    {
        return $this->personId;
    }

    public function getGroupId() : int
    {
        return $this->groupId;
    }
}
