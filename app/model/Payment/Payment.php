<?php

declare(strict_types=1);

namespace Model\Payment;

use Cake\Chronos\Date;
use Consistence\Doctrine\Enum\EnumAnnotation as Enum;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Model\Common\Aggregate;
use Model\Common\EmailAddress;
use Model\Infrastructure\DoctrineNullableEmbeddables\Nullable;
use Model\Payment\DomainEvents\PaymentAmountWasChanged;
use Model\Payment\DomainEvents\PaymentVariableSymbolWasChanged;
use Model\Payment\DomainEvents\PaymentWasCompleted;
use Model\Payment\DomainEvents\PaymentWasCreated;
use Model\Payment\Payment\EmailRecipient;
use Model\Payment\Payment\SentEmail;
use Model\Payment\Payment\State;
use Model\Payment\Payment\Transaction;

use function array_map;
use function array_unique;
use function in_array;

/**
 * @ORM\Entity()
 * @ORM\Table(name="pa_payment")
 */
class Payment extends Aggregate
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private int $id;

    /** @ORM\Column(type="integer") */
    private int $groupId;

    /** @ORM\Column(type="string", length=64) */
    private string $name;

    /**
     * @ORM\OneToMany(targetEntity=EmailRecipient::class, mappedBy="payment", cascade={"persist", "remove"}, orphanRemoval=true)
     *
     * @phpstan-var Collection<int, EmailRecipient>
     * @var Collection<int, EmailRecipient>
     */
    private Collection $emailRecipients;

    /** @ORM\Column(type="integer", nullable=true) */
    private int|null $personId = null;

    /** @ORM\Column(type="float") */
    private float $amount;

    /** @ORM\Column(type="chronos_date") */
    private Date $dueDate;

    /** @ORM\Column(type="variable_symbol", nullable=true, length=10) */
    private VariableSymbol|null $variableSymbol = null;

    /** @ORM\Column(type="smallint", nullable=true) */
    private int|null $constantSymbol = null;

    /** @ORM\Column(type="string", length=64) */
    private string $note = '';

    /**
     * @ORM\Embedded(class=Transaction::class, columnPrefix=false)
     *
     * @Nullable()
     */
    private Transaction|null $transaction = null;

    /** @ORM\Column(type="datetime_immutable", nullable=true) */
    private DateTimeImmutable|null $closedAt = null;

    /** @ORM\Column(type="string", length=64, nullable=true) */
    private string|null $closedByUsername = null;

    /**
     * @ORM\Column(type="string_enum", length=20)
     *
     * @Enum(class=State::class)
     * @var State
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    private $state;

    /**
     * @ORM\OneToMany(targetEntity=SentEmail::class, mappedBy="payment", cascade={"persist", "remove"}, orphanRemoval=true)
     *
     * @var Collection<int, SentEmail>
     */
    private Collection $sentEmails;

    /** @param EmailAddress[] $recipients */
    public function __construct(
        Group $group,
        string $name,
        array $recipients,
        float $amount,
        Date $dueDate,
        VariableSymbol|null $variableSymbol,
        int|null $constantSymbol,
        int|null $personId,
        string $note,
    ) {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Payment amount must be larger than 0');
        }

        $this->groupId  = $group->getId();
        $this->personId = $personId;
        $this->state    = State::get(State::PREPARING);
        $this->amount   = $amount;
        $this->updateDetails($name, $recipients, $dueDate, $constantSymbol, $note);
        $this->variableSymbol = $variableSymbol;
        $this->sentEmails     = new ArrayCollection();

        $this->raise(new PaymentWasCreated($group->getId(), $variableSymbol));
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param EmailAddress[] $recipients
     *
     * @throws PaymentClosed
     */
    public function update(
        string $name,
        array $recipients,
        float $amount,
        Date $dueDate,
        VariableSymbol|null $variableSymbol,
        int|null $constantSymbol,
        string $note,
    ): void {
        $this->checkNotClosed();
        $this->updateDetails($name, $recipients, $dueDate, $constantSymbol, $note);

        if (! VariableSymbol::areEqual($this->variableSymbol, $variableSymbol)) {
            $this->raise(new PaymentVariableSymbolWasChanged($this->groupId, $variableSymbol));
        }

        $this->variableSymbol = $variableSymbol;

        if ($amount !== $this->amount) {
            $this->raise(new PaymentAmountWasChanged($this->groupId, $this->variableSymbol));
        }

        $this->amount = $amount;
    }

    private function complete(DateTimeImmutable $time): void
    {
        $this->checkNotClosed();
        $this->state    = State::get(State::COMPLETED);
        $this->closedAt = $time;
    }

    public function completeManually(DateTimeImmutable $time, string $userFullName): void
    {
        $this->complete($time);
        $this->closedByUsername = $userFullName;
        $this->raise(new PaymentWasCompleted($this->id));
    }

    public function pairWithTransaction(DateTimeImmutable $time, Transaction $transaction): void
    {
        $this->complete($time);
        $this->transaction = $transaction;
        $this->raise(new PaymentWasCompleted($this->id));
    }

    public function recordSentEmail(EmailType $type, DateTimeImmutable $time, string $senderName): void
    {
        $this->sentEmails[] = new SentEmail($this, $type, $time, $senderName);
    }

    public function cancel(DateTimeImmutable $time): void
    {
        if ($this->state->equalsValue(State::CANCELED)) {
            throw new PaymentClosed('Payment is already canceled!');
        }

        $this->state    = State::get(State::CANCELED);
        $this->closedAt = $time;
    }

    public function updateVariableSymbol(VariableSymbol $variableSymbol): void
    {
        $this->checkNotClosed();

        if (! VariableSymbol::areEqual($variableSymbol, $this->variableSymbol)) {
            $this->raise(new PaymentVariableSymbolWasChanged($this->groupId, $variableSymbol));
        }

        $this->variableSymbol = $variableSymbol;
    }

    public function getGroupId(): int
    {
        return $this->groupId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /** @return list<EmailAddress> */
    public function getEmailRecipients(): array
    {
        return $this->emailRecipients
            ->map(fn (EmailRecipient|null $recipient = null) => $recipient->getEmailAddress())
            ->getValues();
    }

    public function getPersonId(): int|null
    {
        return $this->personId;
    }

    public function getAmount(): float
    {
        return $this->amount;
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

    public function getState(): State
    {
        return $this->state;
    }

    public function isClosed(): bool
    {
        $state = $this->state;

        return in_array($state->getValue(), [State::COMPLETED, State::CANCELED], true);
    }

    public function canBePaired(): bool
    {
        return ! $this->isClosed() && $this->variableSymbol !== null;
    }

    /** @return SentEmail[] */
    public function getSentEmails(): array
    {
        return $this->sentEmails->toArray();
    }

    /** @throws PaymentClosed */
    private function checkNotClosed(): void
    {
        if ($this->closedAt !== null) {
            throw new PaymentClosed('Already closed!');
        }
    }

    /** @param EmailAddress[] $recipients */
    private function updateDetails(
        string $name,
        array $recipients,
        Date $dueDate,
        int|null $constantSymbol,
        string $note,
    ): void {
        $this->name            = $name;
        $this->dueDate         = $dueDate;
        $this->constantSymbol  = $constantSymbol;
        $this->note            = $note;
        $this->emailRecipients = new ArrayCollection(array_map(fn (EmailAddress $emailAddress) => new EmailRecipient($this, $emailAddress), array_unique($recipients)));
    }
}
