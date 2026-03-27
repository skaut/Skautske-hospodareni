<?php

declare(strict_types=1);

namespace App\Model\Common\Embeddable;

use App\Model\Bank\Entity\BankTransaction as PersistedBankTransaction;
use App\Model\Bank\Transaction as BankTransaction;
use Cake\Chronos\ChronosDate;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embeddable;
use Nette\SmartObject;

#[Embeddable]
/**
 * @property string           $id
 * @property string|null      $bankAccount
 * @property string           $payer
 * @property string|null      $note
 * @property ChronosDate|null $date
 */
class Transaction
{
    use SmartObject;

    #[Column(name: 'transactionId', type: 'string', length: 191, nullable: true)]
    private ?string $id = null;

    #[Column(type: 'string', length: 64, nullable: true)]
    private ?string $bankAccount = null;

    #[Column(name: 'transaction_payer', type: 'string', nullable: true)]
    private ?string $payer = null;

    #[Column(name: 'transaction_note', type: 'string', nullable: true)]
    private ?string $note = null;

    #[Column(type: 'chronos_date', nullable: true)]
    private ?ChronosDate $date = null;

    public function __construct(string $id, string $bankAccount, string $payer, ?string $note, ?ChronosDate $date)
    {
        $this->id = $id;
        $this->bankAccount = $bankAccount;
        $this->payer = $payer;
        $this->note = $note;
        $this->date = $date;
    }

    public static function fromBankTransaction(BankTransaction|PersistedBankTransaction $transaction): self
    {
        if ($transaction instanceof PersistedBankTransaction) {
            return new self(
                $transaction->getTransactionKey(),
                $transaction->getCounterAccount() ?? '',
                $transaction->getCounterName(),
                $transaction->getNote(),
                new ChronosDate($transaction->getDate()),
            );
        }

        return new self(
            $transaction->getId(),
            $transaction->getBankAccount() ?? '',
            $transaction->getName(),
            $transaction->getNote(),
            new ChronosDate($transaction->getDate()),
        );
    }

    public function getId(): string
    {
        return $this->id ?? '';
    }

    /**
     * TODO: fix some payment transactions in database, that have NULL bank accounts.
     */
    public function getBankAccount(): ?string
    {
        return $this->bankAccount;
    }

    public function getPayer(): ?string
    {
        return $this->payer;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function getDate(): ?ChronosDate
    {
        return $this->date;
    }

    public function isEmpty(): bool
    {
        return $this->id === null
            && $this->bankAccount === null
            && $this->payer === null
            && $this->note === null
            && $this->date === null;
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
