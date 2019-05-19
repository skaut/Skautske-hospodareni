<?php

declare(strict_types=1);

namespace Model\Payment;

use Cake\Chronos\Date;
use Consistence\Doctrine\Enum\EnumAnnotation as Enum;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Fmasa\DoctrineNullableEmbeddables\Annotations\Nullable;
use InvalidArgumentException;
use Model\Common\Aggregate;
use Model\Payment\DomainEvents\PaymentAmountWasChanged;
use Model\Payment\DomainEvents\PaymentVariableSymbolWasChanged;
use Model\Payment\DomainEvents\PaymentWasCompleted;
use Model\Payment\DomainEvents\PaymentWasCreated;
use Model\Payment\Payment\State;
use Model\Payment\Payment\Transaction;
use RuntimeException;
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
     *
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="integer", name="groupId", options={"unsigned"=true})
     *
     * @var int
     */
    private $groupId;

    /**
     * @ORM\Column(type="string", length=64)
     *
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @var string|NULL
     */
    private $email;

    /**
     * @ORM\Column(type="integer", nullable=true, name="personId")
     *
     * @var int|NULL
     */
    private $personId;

    /**
     * @ORM\Column(type="float")
     *
     * @var float
     */
    private $amount;

    /**
     * @ORM\Column(type="chronos_date", name="maturity")
     *
     * @var Date
     */
    private $dueDate;

    /**
     * @ORM\Column(type="variable_symbol", nullable=true, length=10, name="vs")
     *
     * @var VariableSymbol|NULL
     */
    private $variableSymbol;

    /**
     * @ORM\Column(type="smallint", nullable=true, name="ks", options={"unsigned"=true})
     *
     * @var int|NULL
     */
    private $constantSymbol;

    /**
     * @ORM\Column(type="string", length=64)
     *
     * @var string
     */
    private $note = '';

    /**
     * @ORM\Embedded(class=Transaction::class, columnPrefix=false)
     *
     * @var Transaction|NULL
     * @Nullable()
     */
    private $transaction;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true, name="dateClosed")
     *
     * @var DateTimeImmutable|NULL
     */
    private $closedAt;

    /**
     * @ORM\Column(type="string_enum", length=20)
     *
     * @var State
     * @Enum(class=State::class)
     */
    private $state;

    public function __construct(
        Group $group,
        string $name,
        ?string $email,
        float $amount,
        Date $dueDate,
        ?VariableSymbol $variableSymbol,
        ?int $constantSymbol,
        ?int $personId,
        string $note
    ) {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Payment amount must be larger than 0');
        }

        $this->groupId  = $group->getId();
        $this->personId = $personId;
        $this->state    = State::get(State::PREPARING);
        $this->amount   = $amount;
        $this->updateDetails($name, $email, $dueDate, $constantSymbol, $note);
        $this->variableSymbol = $variableSymbol;
        $this->raise(new PaymentWasCreated($group->getId(), $variableSymbol));
    }

    public function getId() : int
    {
        if ($this->id === null) {
            throw new RuntimeException("Can't get ID from not persisted aggregate");
        }

        return $this->id;
    }

    /**
     * @throws PaymentClosed
     */
    public function update(
        string $name,
        ?string $email,
        float $amount,
        Date $dueDate,
        ?VariableSymbol $variableSymbol,
        ?int $constantSymbol,
        string $note
    ) : void {
        $this->checkNotClosed();
        $this->updateDetails($name, $email, $dueDate, $constantSymbol, $note);

        if (! VariableSymbol::areEqual($this->variableSymbol, $variableSymbol)) {
            $this->raise(new PaymentVariableSymbolWasChanged($this->groupId, $variableSymbol));
        }

        $this->variableSymbol = $variableSymbol;

        if ($amount !== $this->amount) {
            $this->raise(new PaymentAmountWasChanged($this->groupId));
        }

        $this->amount = $amount;
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
        if ($this->state->equalsValue(State::CANCELED)) {
            throw new PaymentClosed('Payment is already canceled!');
        }

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
     * @throws PaymentClosed
     */
    private function checkNotClosed() : void
    {
        if ($this->closedAt !== null) {
            throw new PaymentClosed('Already closed!');
        }
    }

    private function updateDetails(
        string $name,
        ?string $email,
        Date $dueDate,
        ?int $constantSymbol,
        string $note
    ) : void {
        $this->name           = $name;
        $this->email          = $email;
        $this->dueDate        = $dueDate;
        $this->constantSymbol = $constantSymbol;
        $this->note           = $note;
    }
}
