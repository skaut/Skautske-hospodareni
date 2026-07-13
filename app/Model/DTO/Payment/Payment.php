<?php

declare(strict_types=1);

namespace App\Model\DTO\Payment;

use App\Model\Common\EmailAddress;
use App\Model\Common\Embeddable\Transaction;
use App\Model\Payment\EmailType;
use App\Model\Payment\Payment\SentEmail;
use App\Model\Payment\Payment\State;
use App\Model\Payment\VariableSymbol;
use Cake\Chronos\ChronosDate;
use DateTimeImmutable;
use Nette\SmartObject;
use Nette\Utils\Strings;

use function array_map;
use function implode;

/**
 * @property int                    $id
 * @property string                 $name
 * @property float                  $amount
 * @property EmailAddress[]         $recipients
 * @property ChronosDate            $dueDate
 * @property VariableSymbol|null    $variableSymbol
 * @property int|null               $constantSymbol
 * @property string                 $note
 * @property bool                   $closed
 * @property State                  $state
 * @property Transaction            $transaction
 * @property DateTimeImmutable|null $closedAt
 * @property string|null            $closedBy
 * @property int|null               $personId
 * @property int                    $groupId
 * @property int|null               $splitFromPaymentId
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
        private ChronosDate $dueDate,
        private ?VariableSymbol $variableSymbol,
        private ?int $constantSymbol,
        private string $note,
        private bool $closed,
        private State $state,
        private ?Transaction $transaction,
        private ?DateTimeImmutable $closedAt,
        private ?string $closedByUsername,
        private ?int $personId,
        private int $groupId,
        private array $sentEmails,
        private ?int $splitFromPaymentId = null,
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

    public function getDueDate(): DateTimeImmutable
    {
        return $this->dueDate->toNative();
    }

    public function getVariableSymbol(): ?VariableSymbol
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

    public function isClosed(): bool
    {
        return $this->closed;
    }

    public function canBePaired(): bool
    {
        return ! $this->closed && $this->variableSymbol !== null;
    }

    public function isOverdue(): bool
    {
        return ! $this->closed && $this->dueDate->isPast() && $this->state->equalsValue(State::PREPARING);
    }

    public function getState(): State
    {
        return $this->state;
    }

    public function getTransaction(): ?Transaction
    {
        return $this->transaction;
    }

    public function getClosedAt(): ?DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function getClosedByUsername(): ?string
    {
        return $this->closedByUsername;
    }

    public function getPersonId(): ?int
    {
        return $this->personId;
    }

    public function getGroupId(): int
    {
        return $this->groupId;
    }

    public function getSplitFromPaymentId(): ?int
    {
        return $this->splitFromPaymentId;
    }

    /** @return SentEmail[] */
    public function getSentEmails(): array
    {
        return $this->sentEmails;
    }

    public function hasSentReminderToday(?DateTimeImmutable $today = null): bool
    {
        $today ??= new DateTimeImmutable();

        foreach ($this->sentEmails as $sentEmail) {
            if (
                $sentEmail->getType()->equalsValue(EmailType::PAYMENT_REMINDER)
                && $sentEmail->getTime()->format('Y-m-d') === $today->format('Y-m-d')
            ) {
                return true;
            }
        }

        return false;
    }

    public function canSendReminder(?DateTimeImmutable $today = null): bool
    {
        $today ??= new DateTimeImmutable();

        return ! $this->isClosed()
            && $this->dueDate->toNative()->format('Y-m-d') < $today->format('Y-m-d')
            && ! $this->hasSentReminderToday($today);
    }
}
