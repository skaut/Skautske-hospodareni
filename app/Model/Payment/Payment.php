<?php

declare(strict_types=1);

namespace App\Model\Payment;

use App\Model\Common\Aggregate;
use App\Model\Common\EmailAddress;
use App\Model\Common\Embeddable\Transaction;
use App\Model\Infrastructure\DoctrineNullableEmbeddables\Nullable;
use App\Model\Payment\DomainEvents\PaymentAmountWasChanged;
use App\Model\Payment\DomainEvents\PaymentVariableSymbolWasChanged;
use App\Model\Payment\DomainEvents\PaymentWasCompleted;
use App\Model\Payment\DomainEvents\PaymentWasCreated;
use App\Model\Payment\Payment\EmailRecipient;
use App\Model\Payment\Payment\SentEmail;
use App\Model\Payment\Payment\State;
use Cake\Chronos\ChronosDate;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

use function array_map;
use function array_unique;
use function in_array;

#[ORM\Entity]
#[ORM\Table(name: 'pa_payment')]
class Payment extends Aggregate
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'integer')]
    private int $groupId;

    #[ORM\Column(type: 'string', length: 64)]
    private string $name;

    /**
     * @var Collection&iterable<EmailRecipient>
     */
    #[ORM\OneToMany(targetEntity: EmailRecipient::class, mappedBy: 'payment', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $emailRecipients;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $personId = null;

    #[ORM\Column(type: 'float')]
    private float $amount;

    #[ORM\Column(type: 'chronos_date')]
    private ChronosDate $dueDate;

    #[ORM\Column(type: 'variable_symbol', nullable: true, length: 10)]
    private ?VariableSymbol $variableSymbol = null;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $constantSymbol = null;

    #[ORM\Column(type: 'string', length: 64)]
    private string $note = '';

    #[ORM\ManyToOne(targetEntity: Payment::class)]
    #[ORM\JoinColumn(name: 'split_from_payment_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Payment $splitFromPayment = null;

    #[ORM\Embedded(class: Transaction::class, columnPrefix: false)]
    #[Nullable]
    private ?Transaction $transaction = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $closedAt = null;

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private ?string $closedByUsername = null;

    /**
     * @var State
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    #[ORM\Column(type: 'payment_state', length: 20)]
    private $state;

    /**
     * @var Collection&iterable<SentEmail>
     */
    #[ORM\OneToMany(targetEntity: SentEmail::class, mappedBy: 'payment', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $sentEmails;

    /** @param EmailAddress[] $recipients */
    public function __construct(
        Group $group,
        string $name,
        array $recipients,
        float $amount,
        ChronosDate $dueDate,
        ?VariableSymbol $variableSymbol,
        ?int $constantSymbol,
        ?int $personId,
        string $note,
        ?Payment $splitFromPayment = null,
    ) {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Payment amount must be larger than 0');
        }

        $this->groupId = (int) $group->getId();
        $this->personId = $personId;
        $this->state = State::get(State::PREPARING);
        $this->amount = $amount;
        $this->updateDetails($name, $recipients, $dueDate, $constantSymbol, $note);
        $this->variableSymbol = $variableSymbol;
        $this->splitFromPayment = $splitFromPayment;
        $this->sentEmails = new ArrayCollection();

        $this->raise(new PaymentWasCreated((int) $group->getId(), $variableSymbol));
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
        ChronosDate $dueDate,
        ?VariableSymbol $variableSymbol,
        ?int $constantSymbol,
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

    public function reduceAmountBySplit(float $amount): void
    {
        $this->checkNotClosed();

        if ($amount <= 0) {
            throw new InvalidArgumentException('Split amount must be larger than 0');
        }

        $remainingAmount = round($this->amount - $amount, 2);

        if ($remainingAmount < 0) {
            throw new InvalidArgumentException('Split amount must not exceed payment amount');
        }

        if ($remainingAmount !== $this->amount) {
            $this->raise(new PaymentAmountWasChanged($this->groupId, $this->variableSymbol));
        }

        $this->amount = $remainingAmount;
    }

    private function complete(DateTimeImmutable $time): void
    {
        $this->checkNotClosed();
        $this->state = State::get(State::COMPLETED);
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

    public function unpairTransaction(): bool
    {
        if ($this->transaction === null || $this->transaction->isEmpty()) {
            return false;
        }

        if (! $this->state->equalsValue(State::COMPLETED)) {
            return false;
        }

        $this->transaction = null;
        $this->closedAt = null;
        $this->closedByUsername = null;
        $this->state = State::get(State::PREPARING);

        return true;
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

        $this->state = State::get(State::CANCELED);
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

    public function updateNote(string $note): void
    {
        $this->note = $note;
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
            ->map(fn (EmailRecipient $recipient) => $recipient->getEmailAddress())
            ->getValues();
    }

    public function getPersonId(): ?int
    {
        return $this->personId;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getDueDate(): ChronosDate
    {
        return $this->dueDate;
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

    public function getSplitFromPayment(): ?Payment
    {
        return $this->splitFromPayment;
    }

    public function getSplitFromPaymentId(): ?int
    {
        return $this->splitFromPayment?->getId();
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
        ChronosDate $dueDate,
        ?int $constantSymbol,
        string $note,
    ): void {
        $this->name = $name;
        $this->dueDate = $dueDate;
        $this->constantSymbol = $constantSymbol;
        $this->note = $note;
        $this->emailRecipients = new ArrayCollection(array_map(fn (EmailAddress $emailAddress) => new EmailRecipient($this, $emailAddress), array_unique($recipients)));
    }
}
