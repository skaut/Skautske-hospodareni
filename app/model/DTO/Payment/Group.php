<?php

namespace Model\DTO\Payment;

use DateTimeImmutable;
use Model\Payment\EmailTemplate;
use Model\Payment\VariableSymbol;
use Nette\SmartObject;

/**
 * @property-read int $id
 * @property-read string|NULL $type
 * @property-read int $unitId
 * @property-read int|NULL $skautisId
 * @property-read string $name
 * @property-read float|NULL $defaultAmount
 * @property-read DateTimeImmutable|NULL $dueDate
 * @property-read int|NULL $constantSymbol
 * @property-read VariableSymbol|NULL $nextVariableSymbol
 * @property-read string $state
 * @property-read EmailTemplate $emailTemplate
 * @property-read int|NULL $smtpId
 * @property-read string $note
 */
class Group
{

    use SmartObject;

    /** @var int */
    private $id;

    /** @var string|NULL */
    private $type;

    /** @var int */
    private $unitId;

    /** @var int|NULL */
    private $skautisId;

    /** @var string */
    private $name;

    /** @var float|NULL */
    private $defaultAmount;

    /** @var DateTimeImmutable|NULL */
    private $dueDate;

    /** @var int|NULL */
    private $constantSymbol;

    /** @var VariableSymbol|NULL */
    private $nextVariableSymbol;

    /** @var string */
    private $state;

    /** @var EmailTemplate */
    private $emailTemplate;

    /** @var int|NULL */
    private $smtpId;

    /** @var string */
    private $note;

    /** @var int|NULL */
    private $bankAccountId;

    public function __construct(
        int $id,
        ?string $type,
        int $unitId,
        ?int $skautisId,
        string $name,
        ?float $defaultAmount,
        ?DateTimeImmutable $dueDate,
        ?int $constantSymbol,
        ?VariableSymbol $nextVariableSymbol,
        string $state,
        EmailTemplate $emailTemplate,
        ?int $smtpId,
        string $note,
        ?int $bankAccountId)
    {
        $this->id = $id;
        $this->type = $type;
        $this->unitId = $unitId;
        $this->skautisId = $skautisId;
        $this->name = $name;
        $this->defaultAmount = $defaultAmount;
        $this->dueDate = $dueDate;
        $this->constantSymbol = $constantSymbol;
        $this->nextVariableSymbol = $nextVariableSymbol;
        $this->state = $state;
        $this->emailTemplate = $emailTemplate;
        $this->smtpId = $smtpId;
        $this->note = $note;
        $this->bankAccountId = $bankAccountId;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getUnitId(): int
    {
        return $this->unitId;
    }

    public function getSkautisId(): ?int
    {
        return $this->skautisId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDefaultAmount() : ?float
    {
        return $this->defaultAmount;
    }

    public function getDueDate() : ?DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function getConstantSymbol() : ?int
    {
        return $this->constantSymbol;
    }

    public function getNextVariableSymbol(): ?VariableSymbol
    {
        return $this->nextVariableSymbol;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getEmailTemplate(): EmailTemplate
    {
        return $this->emailTemplate;
    }

    public function getSmtpId(): ?int
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

}
