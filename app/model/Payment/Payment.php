<?php

namespace Model\Payment;

use DateTimeImmutable;
use Model\Common\AbstractAggregate;
use Model\Payment\DomainEvents\PaymentVariableSymbolWasChanged;
use Model\Payment\DomainEvents\PaymentWasCreated;
use Model\Payment\Payment\State;
use Model\Payment\Payment\Transaction;

class Payment extends AbstractAggregate
{

    /** @var int */
    private $id;

    /** @var Group */
    private $group;

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

    /** @var int|NULL */
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
        ?int $variableSymbol,
        ?int $constantSymbol,
        ?int $personId,
        string $note
    )
    {
        $this->group = $group;
        $this->personId = $personId;
        $this->state = State::get(State::PREPARING);
        $this->updateDetails($name, $email, $amount, $dueDate, $constantSymbol, $note);
        $this->variableSymbol = $variableSymbol;
        $this->raise(new PaymentWasCreated($group->getId(), $variableSymbol));
    }

    /**
     * @throws PaymentClosedException
     */
    public function update(
        string $name,
        ?string $email,
        float $amount,
        DateTimeImmutable $dueDate,
        ?int $variableSymbol,
        ?int $constantSymbol,
        string $note
    ): void
    {
        $this->checkNotClosed();
        $this->updateDetails($name, $email, $amount, $dueDate, $constantSymbol, $note);

        if ($this->variableSymbol !== $variableSymbol) {
            $this->raise(new PaymentVariableSymbolWasChanged($this->group->getId(), $variableSymbol));
        }

        $this->variableSymbol = $variableSymbol;
    }

    public function complete(DateTimeImmutable $time, ?Transaction $transaction = NULL): void
    {
        $this->checkNotClosed();
        $this->transaction = $transaction;
        $this->state = State::get(State::COMPLETED);
        $this->closedAt = $time;
    }

    public function markSent(): void
    {
        $this->checkNotClosed();
        $this->state = State::get(State::SENT);
    }

    public function cancel(DateTimeImmutable $time): void
    {
        $this->checkNotClosed();
        $this->state = State::get(State::CANCELED);
        $this->closedAt = $time;
    }

    public function updateVariableSymbol(int $variableSymbol): void
    {
        $this->checkNotClosed();
        $this->variableSymbol = $variableSymbol;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
	 * @return int
	 */
    public function getGroupId(): int
    {
        return $this->group->getId();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getPersonId(): ?int
    {
        return $this->personId;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getDueDate(): DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function getVariableSymbol()
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

    public function getTransaction(): ?Transaction
    {
        return $this->transaction;
    }

    public function getClosedAt(): ?DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function getState(): State
    {
        return $this->state;
    }

    public function isClosed(): bool
    {
        $state = $this->state;
        return in_array($state->getValue(), [State::COMPLETED, State::CANCELED], TRUE);
    }

    public function canBePaired(): bool
    {
        return !$this->isClosed() && $this->variableSymbol !== NULL;
    }

    /**
     * @throws PaymentClosedException
     */
    private function checkNotClosed(): void
    {
        if ($this->closedAt !== NULL) {
            throw new PaymentClosedException("Already closed!");
        }
    }

    private function updateDetails(
        string $name,
        ?string $email,
        float $amount,
        DateTimeImmutable $dueDate,
        ?int $constantSymbol,
        string $note
    ): void
    {
        $this->name = $name;
        $this->email = $email;
        $this->amount = $amount;
        $this->dueDate = $dueDate;
        $this->constantSymbol = $constantSymbol;
        $this->note = $note;
    }

}
