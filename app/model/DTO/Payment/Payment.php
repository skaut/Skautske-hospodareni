<?php

declare(strict_types=1);

namespace Model\DTO\Payment;

use Cake\Chronos\Date;
use DateTimeImmutable;
use Model\Common\EmailAddress;
use Model\Payment\Payment\SentEmail;
use Model\Payment\Payment\State;
use Model\Payment\Payment\Transaction;
use Model\Payment\VariableSymbol;
use Nette\SmartObject;
use Nette\Utils\Strings;

use function array_map;
use function implode;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read float $amount
 * @property-read EmailAddress[] $recipients
 * @property-read Date $dueDate
 * @property-read VariableSymbol|NULL $variableSymbol
 * @property-read int|NULL $constantSymbol
 * @property-read string $note
 * @property-read bool $closed
 * @property-read State $state
 * @property-read Transaction $transaction
 * @property-read DateTimeImmutable|NULL $closedAt
 * @property-read string|NULL $closedBy
 * @property-read int|NULL $personId
 * @property-read int $groupId
 */
class Payment
{
    use SmartObject;

    /**
     * @param EmailAddress[] $recipients
     * @param SentEmail[]    $sentEmails
     */
    public function __construct(
        private int $id,
        private string $name,
        private float $amount,
        private array $recipients,
        private Date $dueDate,
        private VariableSymbol|null $variableSymbol,
        private int|null $constantSymbol,
        private string $note,
        private bool $closed,
        private State $state,
        private Transaction|null $transaction,
        private DateTimeImmutable|null $closedAt,
        private string|null $closedByUsername,
        private int|null $personId,
        private int $groupId,
        private array $sentEmails,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    /** @return EmailAddress[] */
    public function getEmailRecipients(): array
    {
        return $this->recipients;
    }

    public function getRecipientsString(): string
    {
        return implode(', ', array_map(fn (EmailAddress $emailAddress) => Strings::truncate($emailAddress->getValue(), 35), $this->recipients));
    }

    public function getDueDate(): Date
    {
        return $this->dueDate;
    }

    public function getVariableSymbol(): VariableSymbol|null
    {
        return $this->variableSymbol;
    }

    public function getConstantSymbol(): int|null
    {
        return $this->constantSymbol;
    }

    public function getNote(): string
    {
        return $this->note;
    }

    public function isClosed(): bool
    {
        return $this->closed;
    }

    public function getState(): State
    {
        return $this->state;
    }

    public function getTransaction(): Transaction|null
    {
        return $this->transaction;
    }

    public function getClosedAt(): DateTimeImmutable|null
    {
        return $this->closedAt;
    }

    public function getClosedByUsername(): string|null
    {
        return $this->closedByUsername;
    }

    public function getPersonId(): int|null
    {
        return $this->personId;
    }

    public function getGroupId(): int
    {
        return $this->groupId;
    }

    /** @return SentEmail[] */
    public function getSentEmails(): array
    {
        return $this->sentEmails;
    }
}
