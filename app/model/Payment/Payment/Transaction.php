<?php

declare(strict_types=1);

namespace Model\Payment\Payment;

use Cake\Chronos\ChronosDate;
use Doctrine\ORM\Mapping as ORM;
use Model\Bank\Fio\Transaction as FioTransaction;
use Nette\SmartObject;

/**
 * @ORM\Embeddable()
 *
 * @property-read string $id
 * @property-read string|NULL $bankAccount
 * @property-read string $payer
 * @property-read string|NULL $note
 * @property-read ChronosDate|NULL $date
 */
class Transaction
{
    use SmartObject;

    /** @ORM\Column(type="string", length=64, nullable=true, name="transactionId") */
    private string $id;

    /** @ORM\Column(type="string", length=64, nullable=true) */
    private string $bankAccount;

    /** @ORM\Column(type="string", nullable=true, name="transaction_payer") */
    private string|null $payer = null;

    /** @ORM\Column(type="string", nullable=true, name="transaction_note") */
    private string|null $note = null;

    /** @ORM\Column(type="chronos_date", nullable=true) */
    private ChronosDate|null $date = null;

    public function __construct(string $id, string $bankAccount, string $payer, string|null $note, ChronosDate|null $date)
    {
        $this->id          = $id;
        $this->bankAccount = $bankAccount;
        $this->payer       = $payer;
        $this->note        = $note;
        $this->date        = $date;
    }

    public static function fromFioTransaction(FioTransaction $transaction): self
    {
        return new self(
            $transaction->getId(),
            $transaction->getBankAccount(),
            $transaction->getName(),
            $transaction->getNote(),
            new ChronosDate($transaction->getDate()),
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * TODO: fix some payment transactions in database, that have NULL bank accounts
     */
    public function getBankAccount(): string|null
    {
        return $this->bankAccount;
    }

    public function getPayer(): string|null
    {
        return $this->payer;
    }

    public function getNote(): string|null
    {
        return $this->note;
    }

    public function getDate(): ChronosDate|null
    {
        return $this->date;
    }

    public function equals(self $other): bool
    {
        return $other->id === $this->id
            && $other->bankAccount === $this->bankAccount
            && $other->note === $this->note
            && $other->payer === $this->payer
            && (($other->date === null && $this->date === null) || $other->date->equals($this->date));
    }
}
