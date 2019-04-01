<?php

declare(strict_types=1);

namespace Model\DTO\Payment;

use Cake\Chronos\Date;
use Model\Payment\Summary;
use Model\Payment\VariableSymbol;
use Nette\SmartObject;

/**
 * @property-read int $id
 * @property-read string|NULL $type
 * @property-read int $unitId
 * @property-read int|NULL $skautisId
 * @property-read string $name
 * @property-read float|NULL $defaultAmount
 * @property-read Date|NULL $dueDate
 * @property-read int|NULL $constantSymbol
 * @property-read VariableSymbol|NULL $nextVariableSymbol
 * @property-read string $state
 * @property-read int|NULL $smtpId
 * @property-read string $note
 * @property-read bool $pairable
 */
class Group
{
    use SmartObject;

    /** @var int */
    private $id;

    /** @var string|NULL */
    private $type;

    /** @var int[] */
    private $unitIds;

    /** @var int|NULL */
    private $skautisId;

    /** @var string */
    private $name;

    /** @var float|NULL */
    private $defaultAmount;

    /** @var Date|NULL */
    private $dueDate;

    /** @var int|NULL */
    private $constantSymbol;

    /** @var VariableSymbol|NULL */
    private $nextVariableSymbol;

    /** @var string */
    private $state;

    /** @var int|NULL */
    private $smtpId;

    /** @var string */
    private $note;

    /** @var int|NULL */
    private $bankAccountId;

    /** @var Summary[] */
    private $summary = [];

    /** @var bool */
    private $pairable = false;

    /**
     * @param int[] $unitIds
     */
    public function __construct(
        int $id,
        ?string $type,
        array $unitIds,
        ?int $skautisId,
        string $name,
        ?float $defaultAmount,
        ?Date $dueDate,
        ?int $constantSymbol,
        ?VariableSymbol $nextVariableSymbol,
        string $state,
        ?int $smtpId,
        string $note,
        ?int $bankAccountId
    ) {
        $this->id                 = $id;
        $this->type               = $type;
        $this->unitIds            = $unitIds;
        $this->skautisId          = $skautisId;
        $this->name               = $name;
        $this->defaultAmount      = $defaultAmount;
        $this->dueDate            = $dueDate;
        $this->constantSymbol     = $constantSymbol;
        $this->nextVariableSymbol = $nextVariableSymbol;
        $this->state              = $state;
        $this->smtpId             = $smtpId;
        $this->note               = $note;
        $this->bankAccountId      = $bankAccountId;
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getType() : ?string
    {
        return $this->type;
    }

    /**
     * @return int[]
     */
    public function getUnitIds() : array
    {
        return $this->unitIds;
    }

    public function getUnitId() : int
    {
        return $this->unitId;
    }

    public function getSkautisId() : ?int
    {
        return $this->skautisId;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getDefaultAmount() : ?float
    {
        return $this->defaultAmount;
    }

    public function getDueDate() : ?Date
    {
        return $this->dueDate;
    }

    public function getConstantSymbol() : ?int
    {
        return $this->constantSymbol;
    }

    public function getNextVariableSymbol() : ?VariableSymbol
    {
        return $this->nextVariableSymbol;
    }

    public function getState() : string
    {
        return $this->state;
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
        return $this->bankAccountId;
    }

    /**
     * @param Summary[] $summary
     */
    public function setSummary(array $summary) : void
    {
        $this->summary = $summary;
    }

    /**
     * @return Summary[]
     */
    public function getSummary() : array
    {
        return $this->summary;
    }

    public function isPairable() : bool
    {
        return $this->pairable;
    }

    public function setPairable(bool $pairable) : void
    {
        $this->pairable = $pairable;
    }
}
