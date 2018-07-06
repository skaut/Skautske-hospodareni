<?php

declare(strict_types=1);

namespace Model\Payment;

use DateTimeImmutable;
use Model\Common\AbstractAggregate;
use Model\Payment\DomainEvents\PaymentVariableSymbolWasChanged;
use Model\Payment\DomainEvents\PaymentWasCompleted;
use Model\Payment\DomainEvents\PaymentWasCreated;
use Model\Payment\Payment\State;
use Model\Payment\Payment\Transaction;
use function in_array;

class Payment extends AbstractAggregate
{
    /** @var int */
    private $id;

    /** @var int */
    private $groupId;

    /** @var string */
    private $name;

    /** @var string|NULL */
    private $email;

    /** @var int|NULL */
    private $personId;

    /** @var float */
    private $amount;

    /** @var DateTimeImmutable */
    private $dueDate;

    /** @var VariableSymbol|NULL */
    private $variableSymbol;

    /** @var int|NULL */
    private $constantSymbol;

    /** @var string */
    private $note = '';

    /** @var Transaction|NULL */
    private $transaction;

    /** @var DateTimeImmutable|NULL */
    private $closedAt;

    /** @var State */
    private $state;

    public function __construct(
        Group $group,
        string $name,
        ?string $email,
        float $amount,
        DateTimeImmutable $dueDate,
        ?VariableSymbol $variableSymbol,
        ?int $constantSymbol,
        ?int $personId,
        string $note
    ) {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Payment amount must be larger than 0');
        }

        $this->groupId  = $group->getId();
        $this->personId = $personId;
        $this->state    = State::get(State::PREPARING);
        $this->updateDetails($name, $email, $amount, $dueDate, $constantSymbol, $note);
        $this->variableSymbol = $variableSymbol;
        $this->raise(new PaymentWasCreated($group->getId(), $variableSymbol));
    }

    public function getId() : int
    {
        if ($this->id === null) {
            throw new \RuntimeException("Can't get ID from not persisted aggregate");
        }

        return $this->id;
    }

    /**
     * @throws PaymentClosedException
     */
    public function update(
        string $name,
        ?string $email,
        float $amount,
        DateTimeImmutable $dueDate,
        ?VariableSymbol $variableSymbol,
        ?int $constantSymbol,
        string $note
    ) : void {
        $this->checkNotClosed();
        $this->updateDetails($name, $email, $amount, $dueDate, $constantSymbol, $note);

        if ($this->variableSymbol !== $variableSymbol) {
            $this->raise(new PaymentVariableSymbolWasChanged($this->groupId, $variableSymbol));
        }

        $this->variableSymbol = $variableSymbol;
    }

    public function complete(DateTimeImmutable $time, ?Transaction $transaction = null) : void
    {
        $this->checkNotClosed();
        $this->transaction = $transaction;
        $this->state       = State::get(State::COMPLETED);
        $this->closedAt    = $time;

        $this->raise(new PaymentWasCompleted($this->id));
    }

    public function markSent() : void
    {
        $this->checkNotClosed();
        $this->state = State::get(State::SENT);
    }

    public function cancel(DateTimeImmutable $time) : void
    {
        $this->checkNotClosed();
        $this->state    = State::get(State::CANCELED);
        $this->closedAt = $time;
    }

    public function updateVariableSymbol(VariableSymbol $variableSymbol) : void
    {
        $this->checkNotClosed();

        if (! VariableSymbol::areEqual($variableSymbol, $this->variableSymbol)) {
            $this->raise(new PaymentVariableSymbolWasChanged($this->groupId, $variableSymbol));
        }

        $this->variableSymbol = $variableSymbol;
    }

    public function getGroupId() : int
    {
        return $this->groupId;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getEmail() : ?string
    {
        return $this->email;
    }

    public function getPersonId() : ?int
    {
        return $this->personId;
    }

    public function getAmount() : float
    {
        return $this->amount;
    }

    public function getDueDate() : DateTimeImmutable
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

    public function getTransaction() : ?Transaction
    {
        return $this->transaction;
    }

    public function getClosedAt() : ?DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function getState() : State
    {
        return $this->state;
    }

    public function isClosed() : bool
    {
        $state = $this->state;
        return in_array($state->getValue(), [State::COMPLETED, State::CANCELED], true);
    }

    public function canBePaired() : bool
    {
        return ! $this->isClosed() && $this->variableSymbol !== null;
    }

    /**
     * @throws PaymentClosedException
     */
    private function checkNotClosed() : void
    {
        if ($this->closedAt !== null) {
            throw new PaymentClosedException('Already closed!');
        }
    }

    private function updateDetails(
        string $name,
        ?string $email,
        float $amount,
        DateTimeImmutable $dueDate,
        ?int $constantSymbol,
        string $note
    ) : void {
        $this->name           = $name;
        $this->email          = $email;
        $this->amount         = $amount;
        $this->dueDate        = $dueDate;
        $this->constantSymbol = $constantSymbol;
        $this->note           = $note;
    }
}
