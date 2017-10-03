<?php

declare(strict_types=1);

namespace Model\Payment;

use Model\Payment\Group\EmailTemplate;
use Model\Payment\Group\SkautisEntity;
use Model\Payment\Repositories\IBankAccountRepository;

class Group
{

    /** @var int */
    private $id;

    /** @var int */
    private $unitId;

    /** @var SkautisEntity|NULL */
    private $object;

    /** @var string|NULL */
    private $name;

    /** @var float|NULL */
    private $defaultAmount;

    /** @var \DateTimeImmutable|NULL */
    private $dueDate;

    /** @var int|NULL */
    private $constantSymbol;

    /** @var int|NULL */
    private $nextVariableSymbol;

    /** @var string */
    private $state = self::STATE_OPEN;

    /** @var \DateTimeImmutable|NULL */
    private $createdAt;

    /** @var \DateTimeImmutable|NULL */
    private $lastPairing;

    /** @var EmailTemplate */
    private $emailTemplate;

    /** @var int|NULL */
    private $smtpId;

    /** @var string */
    private $note = '';

    /** @var int|NULL */
    private $bankAccountId;

    const STATE_OPEN = 'open';
    const STATE_CLOSED = 'closed';

    public function __construct(
        int $unitId,
        ?SkautisEntity $object,
        string $name,
        ?float $defaultAmount,
        ?\DateTimeImmutable $dueDate,
        ?int $constantSymbol,
        ?int $nextVariableSymbol,
        \DateTimeImmutable $createdAt,
        EmailTemplate $emailTemplate,
        ?int $smtpId,
        ?BankAccount $bankAccount
    )
    {
        $this->unitId = $unitId;
        $this->object = $object;
        $this->name = $name;
        $this->defaultAmount = $defaultAmount;
        $this->dueDate = $dueDate;
        $this->constantSymbol = $constantSymbol;
        $this->nextVariableSymbol = $nextVariableSymbol;
        $this->createdAt = $createdAt;
        $this->emailTemplate = $emailTemplate;
        $this->smtpId = $smtpId;
        $this->changeBankAccount($bankAccount);
    }

    public function update(
        string $name,
        ?float $defaultAmount,
        ?\DateTimeImmutable $dueDate,
        ?int $constantSymbol,
        ?int $nextVariableSymbol,
        EmailTemplate $emailTemplate,
        ?int $smtpId,
        ?BankAccount $bankAccount) : void
    {
        $this->name = $name;
        $this->defaultAmount = $defaultAmount;
        $this->dueDate = $dueDate;
        $this->constantSymbol = $constantSymbol;
        $this->nextVariableSymbol = $nextVariableSymbol;
        $this->emailTemplate = $emailTemplate;
        $this->smtpId = $smtpId;
        $this->changeBankAccount($bankAccount);
    }

    public function open(string $note): void
    {
        if ($this->state === self::STATE_OPEN) {
            return;
        }
        $this->state = self::STATE_OPEN;
        $this->note = $note;
    }

    public function close(string $note): void
    {
        if($this->state === self::STATE_CLOSED) {
            return;
        }
        $this->state = self::STATE_CLOSED;
        $this->note = $note;
    }

    public function removeBankAccount(): void
    {
        $this->bankAccountId = NULL;
    }

    /**
     * @throws BankAccountNotFoundException
     */
    public function changeUnit(int $unitId, IUnitResolver $unitResolver, IBankAccountRepository $bankAccountRepository): void
    {
        if($this->bankAccountId === NULL) {
            $this->unitId = $unitId;
            return;
        }

        $currentOfficialUnit = $unitResolver->getOfficialUnitId($this->unitId);
        $newOfficialUnit = $unitResolver->getOfficialUnitId($unitId);

        // different official unit
        if($currentOfficialUnit !== $newOfficialUnit) {
            $this->unitId = $unitId;
            $this->bankAccountId = NULL;
            return;
        }

        // unit -> official unit
        if($unitId === $newOfficialUnit) {
            $this->unitId = $unitId;
            return;
        }

        $bankAccount = $bankAccountRepository->find($this->bankAccountId);

        if( ! $bankAccount->isAllowedForSubunits()) {
            $this->bankAccountId = NULL;
        }

        $this->unitId = $unitId;
    }


    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getUnitId(): int
    {
        return $this->unitId;
    }

    public function getObject(): ?SkautisEntity
    {
        return $this->object;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return float|NULL
     */
    public function getDefaultAmount(): ?float
    {
        return $this->defaultAmount;
    }

    /**
     * @return \DateTimeImmutable|NULL
     */
    public function getDueDate(): ?\DateTimeImmutable
    {
        return $this->dueDate;
    }

    /**
     * @return int|NULL
     */
    public function getConstantSymbol(): ?int
    {
        return $this->constantSymbol;
    }

    /**
     * @return int|NULL
     */
    public function getNextVariableSymbol(): ?int
    {
        return $this->nextVariableSymbol;
    }

    /**
     * @return string
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * @return \DateTimeImmutable|NULL
     */
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getEmailTemplate(): EmailTemplate
    {
        return $this->emailTemplate;
    }

    /**
     * Update last pairing time
     * @param \DateTimeImmutable $at
     */
    public function updateLastPairing(\DateTimeImmutable $at): void
    {
        $this->lastPairing = $at;
    }

    public function invalidateLastPairing(): void
    {
        $this->lastPairing = NULL;
    }

    /**
     * @return \DateTimeImmutable|NULL
     */
    public function getLastPairing(): ?\DateTimeImmutable
    {
        return $this->lastPairing;
    }

    /**
     * @return int|NULL
     */
    public function getSmtpId() : ?int
    {
        return $this->smtpId;
    }

    public function getNote(): string
    {
        return $this->note;
    }

    public function getBankAccountId(): ?int
    {
        return $this->bankAccountId;
    }

    public function isOpen(): bool
    {
        return $this->state === self::STATE_OPEN;
    }

    private function changeBankAccount(?BankAccount $bankAccount): void
    {
        if($bankAccount === NULL) {
            $this->bankAccountId = NULL;
            return;
        }

        if($bankAccount->getUnitId() !== $this->unitId && !$bankAccount->isAllowedForSubunits()) {
            throw new \InvalidArgumentException("Unit owning this group has no acces to this bank account");
        }

        $this->bankAccountId = $bankAccount->getId();
    }

}
