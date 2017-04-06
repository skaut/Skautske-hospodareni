<?php

namespace Model\Payment;

use DateTimeImmutable;
use Model\Payment\Payment\State;
use Model\Payment\Payment\Transaction;

class Payment
{

    /** @var int */
    private $id;

    /** @var Group */
    private $group;

    /** @var string */
    private $name;

    /** @var string */
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
        string $email,
        float $amount,
        DateTimeImmutable $dueDate,
        ?int $variableSymbol,
        ?int $constantSymbol,
        ?int $personId,
        string $note
    )
    {
        $this->group = $group;
        $this->name = $name;
        $this->email = $email;
        $this->amount = $amount;
        $this->dueDate = $dueDate;
        $this->variableSymbol = $variableSymbol;
        $this->constantSymbol = $constantSymbol;
        $this->personId = $personId;
        $this->state = State::get(State::PREPARING);
        $this->note = $note;
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

	public function cancel(DateTimeImmutable $time)
    {
        $this->checkNotClosed();
        $this->state = State::get(State::CANCELED);
        $this->closedAt = $time;
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

    /**
     * @return string
     */
    public function getEmail(): string
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

    public function isFinished(): bool
	{
		$state = $this->state;
		return $state->equalsValue(State::COMPLETED) || $state->equalsValue(State::CANCELED);
	}

    /**
     * @throws \Exception
     */
    private function checkNotClosed(): void
    {
        if ($this->closedAt !== NULL) {
            throw new PaymentFinishedException("Already closed!"); // todo: replace with custom exception
        }
    }

}
