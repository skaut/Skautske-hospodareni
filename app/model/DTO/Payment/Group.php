<?php

namespace Model\DTO\Payment;

use DateTimeImmutable;
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
 * @property-read int|NULL $nextVariableSymbol
 * @property-read string $state
 * @property-read string $emailTemplate
 * @property-read int|NULL $smtpId
 * @property-read string $note
 * @property-read Summary[] $stats
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

    /** @var int|NULL */
    private $nextVariableSymbol;

    /** @var string */
    private $state;

    /** @var string */
    private $emailTemplate;

    /** @var int|NULL */
    private $smtpId;

    /** @var string */
    private $note;

    /** @var Summary[] */
    private $stats;

    public function __construct(
        int $id,
        ?string $type,
        int $unitId,
        ?int $skautisId,
        string $name,
        ?float $defaultAmount,
        ?DateTimeImmutable $dueDate,
        ?int $constantSymbol,
        ?int $nextVariableSymbol,
        string $state,
        string $emailTemplate,
        ?int $smtpId,
        string $note,
        array $stats)
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
        $this->stats = $stats;
    }


    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @return int
     */
    public function getUnitId(): int
    {
        return $this->unitId;
    }

    public function getSkautisId(): ?int
    {
        return $this->skautisId;
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
    public function getDefaultAmount() : ?float
    {
        return $this->defaultAmount;
    }

    /**
     * @return \DateTimeImmutable|NULL
     */
    public function getDueDate() : ?DateTimeImmutable
    {
        return $this->dueDate;
    }

    /**
     * @return int|NULL
     */
    public function getConstantSymbol() : ?int
    {
        return $this->constantSymbol;
    }

    /**
     * @return int|NULL
     */
    public function getNextVariableSymbol() : ?int
    {
        return $this->nextVariableSymbol;
    }

    public function getState(): string
    {
        return $this->state;
    }

    /**
     * @return string
     */
    public function getEmailTemplate(): string
    {
        return $this->emailTemplate;
    }

    /**
     * @return string
     */
    public function getSmtpId(): ?int
    {
        return $this->smtpId;
    }

    public function getNote(): string
    {
        return $this->note;
    }

    /**
     * @return Summary[]
     */
    public function getStats(): array
    {
        return $this->stats;
    }

}
