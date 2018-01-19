<?php

declare(strict_types=1);

namespace Model\Payment;

use Doctrine\Common\Collections\ArrayCollection;
use Model\Payment\Group\Email;
use Model\Payment\Group\PaymentDefaults;
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

    /** @var PaymentDefaults */
    private $paymentDefaults;

    /** @var string */
    private $state = self::STATE_OPEN;

    /** @var \DateTimeImmutable|NULL */
    private $createdAt;

    /** @var \DateTimeImmutable|NULL */
    private $lastPairing;

    /** @var ArrayCollection|Email[] */
    private $emails;

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
        PaymentDefaults $paymentDefaults,
        \DateTimeImmutable $createdAt,
        EmailTemplate $emailTemplate,
        ?int $smtpId,
        ?BankAccount $bankAccount
    )
    {
        $this->unitId = $unitId;
        $this->object = $object;
        $this->name = $name;
        $this->paymentDefaults = $paymentDefaults;
        $this->createdAt = $createdAt;
        $this->smtpId = $smtpId;

        $this->emails = new ArrayCollection([
            new Email($this, EmailType::get(EmailType::PAYMENT_INFO), $emailTemplate)
        ]);

        $this->changeBankAccount($bankAccount);
    }

    public function update(
        string $name,
        PaymentDefaults $paymentDefaults,
        EmailTemplate $emailTemplate,
        ?int $smtpId,
        ?BankAccount $bankAccount) : void
    {
        $this->name = $name;
        $this->paymentDefaults = $paymentDefaults;
        $this->updateEmail(EmailType::get(EmailType::PAYMENT_INFO), $emailTemplate);
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

    private function updateEmail(EmailType $type, EmailTemplate $template): void
    {
        $email = $this->getEmail($type);

        if($email !== NULL) {
            $email->setTemplate($template);
            return;
        }

        $this->emails->add(new Email($this, $type, $template));
    }

    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUnitId(): int
    {
        return $this->unitId;
    }

    public function getObject(): ?SkautisEntity
    {
        return $this->object;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPaymentDefaults(): PaymentDefaults
    {
        return $this->paymentDefaults;
    }

    /**
     * @deprecated Use Group::getPaymentDefaults()
     */
    public function getDefaultAmount(): ?float
    {
        return $this->paymentDefaults->getAmount();
    }

    /**
     * @deprecated Use Group::getPaymentDefaults()
     */
    public function getDueDate(): ?\DateTimeImmutable
    {
        return $this->paymentDefaults->getDueDate();
    }

    /**
     * @deprecated Use Group::getPaymentDefaults()
     */
    public function getConstantSymbol(): ?int
    {
        return $this->paymentDefaults->getConstantSymbol();
    }

    /**
     * @deprecated Use Group::getPaymentDefaults()
     */
    public function getNextVariableSymbol(): ?VariableSymbol
    {
        return $this->paymentDefaults->getNextVariableSymbol();
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getEmailTemplate(): EmailTemplate
    {
        $email = $this->getEmail(EmailType::get(EmailType::PAYMENT_INFO));

        if($email !== NULL) {
            return $email->getTemplate();
        }

        return new EmailTemplate('', '');
    }

    public function updateLastPairing(\DateTimeImmutable $at): void
    {
        $this->lastPairing = $at;
    }

    public function invalidateLastPairing(): void
    {
        $this->lastPairing = NULL;
    }

    public function getLastPairing(): ?\DateTimeImmutable
    {
        return $this->lastPairing;
    }

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

    private function getEmail(EmailType $type): ?Email
    {
        return $this->emails->filter(function(Email $email) use ($type) {
            return $email->getType()->equals($type);
        })->first();
    }

}
