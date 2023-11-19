<?php

declare(strict_types=1);

namespace Model\DTO\Payment;

use Cake\Chronos\ChronosDate;
use Model\Google\OAuthId;
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
 * @property-read OAuthId|NULL $oAuthId
 * @property-read string $note
 */
class Group
{
    use SmartObject;

    /** @param int[] $unitIds */
    public function __construct(
        private int                 $id,
        private string|null         $type = null,
        private array               $unitIds,
        private int|null            $skautisId = null,
        private string              $name,
        private float|null          $defaultAmount = null,
        private ChronosDate|null    $dueDate = null,
        private int|null            $constantSymbol = null,
        private VariableSymbol|null $nextVariableSymbol = null,
        private string              $state,
        private OAuthId|null        $oAuthId = null,
        private string              $note,
        private int|null            $bankAccountId = null,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): string|null
    {
        return $this->type;
    }

    /** @return int[] */
    public function getUnitIds(): array
    {
        return $this->unitIds;
    }

    public function getUnitId(): int
    {
        return $this->unitId;
    }

    public function getSkautisId(): int|null
    {
        return $this->skautisId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDefaultAmount(): float|null
    {
        return $this->defaultAmount;
    }

    public function getDueDate(): ChronosDate|null
    {
        return $this->dueDate;
    }

    public function getConstantSymbol(): int|null
    {
        return $this->constantSymbol;
    }

    public function getNextVariableSymbol(): VariableSymbol|null
    {
        return $this->nextVariableSymbol;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getOAuthId(): OAuthId|null
    {
        return $this->oAuthId;
    }

    public function getNote(): string
    {
        return $this->note;
    }

    public function getBankAccountId(): int|null
    {
        return $this->bankAccountId;
    }
}
