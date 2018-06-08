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

    /** @var Group\BankAccount|NULL */
    private $bankAccount;

    /** @var ArrayCollection|Email[] */
    private $emails;

    /** @var int|NULL */
    private $smtpId;

    /** @var string */
    private $note = '';

    public const STATE_OPEN = 'open';
    public const STATE_CLOSED = 'closed';

    /**
     * @param EmailTemplate[] $emails
     */
    public function __construct(
        int $unitId,
        ?SkautisEntity $object,
        string $name,
        PaymentDefaults $paymentDefaults,
        \DateTimeImmutable $createdAt,
        array $emails,
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

        $this->emails = new ArrayCollection();

        if ( ! isset($emails[EmailType::PAYMENT_INFO])) {
            throw new \InvalidArgumentException("Required email template '" . EmailType::PAYMENT_INFO . "' is missing");
        }

        foreach ($emails as $typeKey => $template) {
            $this->updateEmail(EmailType::get($typeKey), $template);
        }

        $this->changeBankAccount($bankAccount);
    }

    public function update(string $name, PaymentDefaults $paymentDefaults, ?int $smtpId, ?BankAccount $bankAccount) : void
    {
        $this->name = $name;
        $this->paymentDefaults = $paymentDefaults;
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
        $this->bankAccount = NULL;
    }

    /**
     * @throws BankAccountNotFoundException
     */
    public function changeUnit(int $unitId, IUnitResolver $unitResolver, IBankAccountRepository $bankAccountRepository): void
    {
        $this->unitId = $unitId;

        if ($this->bankAccount === NULL) {
            return;
        }

        $currentOfficialUnit = $unitResolver->getOfficialUnitId($this->unitId);
        $newOfficialUnit = $unitResolver->getOfficialUnitId($unitId);

        // different official unit
        if($currentOfficialUnit !== $newOfficialUnit) {
            $this->bankAccount = NULL;
            return;
        }

        // unit -> official unit
        if($unitId === $newOfficialUnit) {
            return;
        }

        $bankAccount = $bankAccountRepository->find($this->bankAccount->getId());

        if( ! $bankAccount->isAllowedForSubunits()) {
            $this->bankAccount = NULL;
        }
    }

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

    public function getDefaultAmount(): ?float
    {
        return $this->paymentDefaults->getAmount();
    }

    public function getDueDate(): ?\DateTimeImmutable
    {
        return $this->paymentDefaults->getDueDate();
    }

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

    public function getEmailTemplate(EmailType $type): ?EmailTemplate
    {
        $email = $this->getEmail($type);

        return $email !== NULL ? $email->getTemplate() : NULL;
    }

    public function isEmailEnabled(EmailType $type): bool
    {
        $email = $this->getEmail($type);

        return $email !== NULL && $email->isEnabled();
    }

    public function updateLastPairing(\DateTimeImmutable $at): void
    {
        if ($this->bankAccount !== NULL) {
            $this->bankAccount = $this->bankAccount->updateLastPairing($at);
        }
    }

    public function invalidateLastPairing(): void
    {
        if ($this->bankAccount !== NULL) {
            $this->bankAccount = $this->bankAccount->invalidateLastPairing();
        }
    }

    public function getLastPairing(): ?\DateTimeImmutable
    {
        return $this->bankAccount !== NULL ? $this->bankAccount->getLastPairing() : NULL;
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
        return $this->bankAccount !== NULL ? $this->bankAccount->getId() : NULL;
    }

    private function changeBankAccount(?BankAccount $bankAccount): void
    {
        if($bankAccount === NULL) {
            $this->bankAccount = NULL;
            return;
        }

        if($bankAccount->getUnitId() !== $this->unitId && !$bankAccount->isAllowedForSubunits()) {
            throw new \InvalidArgumentException("Unit owning this group has no acces to this bank account");
        }

        $this->bankAccount = Group\BankAccount::create($bankAccount->getId());
    }

    public function updateEmail(EmailType $type, EmailTemplate $template): void
    {
        $email = $this->getEmail($type);

        if ($email !== NULL) {
            $email->updateTemplate($template);
            return;
        }

        $this->emails->add(new Email($this, $type, $template));
    }

    public function disableEmail(EmailType $type): void
    {
        $email = $this->getEmail($type);

        if($email !== NULL) {
            $email->disable();
        }
    }

    private function getEmail(EmailType $type): ?Email
    {
        foreach($this->emails as $email) {
            if($email->getType()->equals($type)) {
                return $email;
            }
        }

        return NULL;
    }

}
