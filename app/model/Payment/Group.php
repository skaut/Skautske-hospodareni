<?php

declare(strict_types=1);

namespace Model\Payment;

use Assert\Assertion;
use Cake\Chronos\Date;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Fmasa\DoctrineNullableEmbeddables\Annotations\Nullable;
use Model\Payment\Group\Email;
use Model\Payment\Group\PaymentDefaults;
use Model\Payment\Group\SkautisEntity;
use Model\Payment\Group\Unit;
use Model\Payment\Services\IBankAccountAccessChecker;

/**
 * @ORM\Entity()
 * @ORM\Table(name="pa_group")
 */
class Group
{
    /**
     * @var int
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private $id;

    /**
     * @var ArrayCollection|Unit[]
     * @ORM\OneToMany(
     *     targetEntity=Unit::class,
     *     mappedBy="group",
     *     orphanRemoval=true,
     *     cascade={"persist", "remove"},
     *     indexBy="index"
     * )
     */
    private $units;

    /**
     * @var SkautisEntity|NULL
     * @ORM\Embedded(class=SkautisEntity::class, columnPrefix=false)
     * @Nullable()
     */
    private $object;

    /**
     * @var string
     * @ORM\Column(type="string", name="label", length=64)
     */
    private $name;

    /**
     * @var PaymentDefaults
     * @ORM\Embedded(class=PaymentDefaults::class, columnPrefix=false)
     */
    private $paymentDefaults;

    /**
     * @var string
     * @ORM\Column(type="string", length=20)
     */
    private $state = self::STATE_OPEN;

    /**
     * @var \DateTimeImmutable|NULL
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private $createdAt;

    /**
     * @var Group\BankAccount|NULL
     * @ORM\Embedded(class=Group\BankAccount::class, columnPrefix=false)
     * @Nullable()
     */
    private $bankAccount;

    /**
     * @var ArrayCollection|Email[]
     * @ORM\OneToMany(targetEntity=Email::class, mappedBy="group", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    private $emails;

    /**
     * @var int|NULL
     * @ORM\Column(type="integer", options={"unsigned"=true}, nullable=true)
     */
    private $smtpId;

    /**
     * @var string
     * @ORM\Column(type="string", name="state_info", length=250)
     */
    private $note = '';

    public const STATE_OPEN   = 'open';
    public const STATE_CLOSED = 'closed';

    /**
     * @param int[]           $unitIds
     * @param EmailTemplate[] $emails
     */
    public function __construct(
        array $unitIds,
        ?SkautisEntity $object,
        string $name,
        PaymentDefaults $paymentDefaults,
        \DateTimeImmutable $createdAt,
        array $emails,
        ?int $smtpId,
        ?BankAccount $bankAccount,
        IBankAccountAccessChecker $bankAccountAccessChecker
    ) {
        Assertion::notEmpty($unitIds);
        $this->object          = $object;
        $this->name            = $name;
        $this->paymentDefaults = $paymentDefaults;
        $this->createdAt       = $createdAt;
        $this->smtpId          = $smtpId;

        $this->emails = new ArrayCollection();
        $this->units  = new ArrayCollection();

        foreach ($unitIds as $unitId) {
            $this->units->add(new Unit($this, $unitId));
        }

        if (! isset($emails[EmailType::PAYMENT_INFO])) {
            throw new \InvalidArgumentException("Required email template '" . EmailType::PAYMENT_INFO . "' is missing");
        }

        foreach ($emails as $typeKey => $template) {
            $this->updateEmail(EmailType::get($typeKey), $template);
        }

        $this->changeBankAccount($bankAccount, $bankAccountAccessChecker);
    }

    public function update(
        string $name,
        PaymentDefaults $paymentDefaults,
        ?int $smtpId,
        ?BankAccount $bankAccount,
        IBankAccountAccessChecker $bankAccountAccessChecker
    ) : void {
        $this->changeBankAccount($bankAccount, $bankAccountAccessChecker);

        $this->name            = $name;
        $this->paymentDefaults = $paymentDefaults;
        $this->smtpId          = $smtpId;
    }

    public function open(string $note) : void
    {
        if ($this->state === self::STATE_OPEN) {
            return;
        }
        $this->state = self::STATE_OPEN;
        $this->note  = $note;
    }

    public function close(string $note) : void
    {
        if ($this->state === self::STATE_CLOSED) {
            return;
        }
        $this->state = self::STATE_CLOSED;
        $this->note  = $note;
    }

    public function removeBankAccount() : void
    {
        $this->bankAccount = null;
    }

    /**
     * @param int[] $unitIds
     */
    public function changeUnits(array $unitIds, IBankAccountAccessChecker $accessChecker) : void
    {
        $this->units->clear();
        foreach ($unitIds as $unitId) {
            $this->units->add(new Unit($this, $unitId));
        }

        $bankAccount = $this->bankAccount;

        if ($bankAccount === null || $accessChecker->allUnitsHaveAccessToBankAccount($unitIds, $bankAccount->getId())) {
            return;
        }

        $this->bankAccount = null;
    }

    public function getId() : ?int
    {
        return $this->id;
    }

    /**
     * @return int[]
     */
    public function getUnitIds() : array
    {
        return $this->units->map(
            function (Unit $unit) : int {
                return $unit->getUnitId();
            }
        )->toArray();
    }

    public function getObject() : ?SkautisEntity
    {
        return $this->object;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getPaymentDefaults() : PaymentDefaults
    {
        return $this->paymentDefaults;
    }

    public function getDefaultAmount() : ?float
    {
        return $this->paymentDefaults->getAmount();
    }

    public function getDueDate() : ?Date
    {
        return $this->paymentDefaults->getDueDate();
    }

    public function getConstantSymbol() : ?int
    {
        return $this->paymentDefaults->getConstantSymbol();
    }

    /**
     * @deprecated Use Group::getPaymentDefaults()
     */
    public function getNextVariableSymbol() : ?VariableSymbol
    {
        return $this->paymentDefaults->getNextVariableSymbol();
    }

    public function getState() : string
    {
        return $this->state;
    }

    public function getCreatedAt() : ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getEmailTemplate(EmailType $type) : ?EmailTemplate
    {
        $email = $this->getEmail($type);

        return $email !== null ? $email->getTemplate() : null;
    }

    public function isEmailEnabled(EmailType $type) : bool
    {
        $email = $this->getEmail($type);

        return $email !== null && $email->isEnabled();
    }

    public function updateLastPairing(\DateTimeImmutable $at) : void
    {
        if ($this->bankAccount === null) {
            return;
        }

        $this->bankAccount = $this->bankAccount->updateLastPairing($at);
    }

    public function invalidateLastPairing() : void
    {
        if ($this->bankAccount === null) {
            return;
        }

        $this->bankAccount = $this->bankAccount->invalidateLastPairing();
    }

    public function getLastPairing() : ?\DateTimeImmutable
    {
        return $this->bankAccount !== null ? $this->bankAccount->getLastPairing() : null;
    }

    public function getSmtpId() : ?int
    {
        return $this->smtpId;
    }

    public function getNote() : string
    {
        return $this->note;
    }

    public function getBankAccountId() : ?int
    {
        return $this->bankAccount !== null ? $this->bankAccount->getId() : null;
    }

    private function changeBankAccount(?BankAccount $bankAccount, IBankAccountAccessChecker $accessChecker) : void
    {
        if ($bankAccount === null) {
            $this->bankAccount = null;
            return;
        }

        $unitIds = $this->getUnitIds();

        if (! $accessChecker->allUnitsHaveAccessToBankAccount($unitIds, $bankAccount->getId())) {
            throw NoAccessToBankAccount::forUnits($unitIds, $bankAccount->getId());
        }

        $this->bankAccount = Group\BankAccount::create($bankAccount->getId());
    }

    public function updateEmail(EmailType $type, EmailTemplate $template) : void
    {
        $email = $this->getEmail($type);

        if ($email !== null) {
            $email->updateTemplate($template);
            return;
        }

        $this->emails->add(new Email($this, $type, $template));
    }

    public function disableEmail(EmailType $type) : void
    {
        $email = $this->getEmail($type);

        if ($email === null) {
            return;
        }

        $email->disable();
    }

    private function getEmail(EmailType $type) : ?Email
    {
        foreach ($this->emails as $email) {
            if ($email->getType()->equals($type)) {
                return $email;
            }
        }

        return null;
    }
}
