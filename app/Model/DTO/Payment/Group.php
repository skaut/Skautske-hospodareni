<?php

declare(strict_types=1);

namespace App\Model\DTO\Payment;

use App\Model\Google\OAuthId;
use App\Model\Payment\VariableSymbol;
use Cake\Chronos\ChronosDate;
use DateTimeImmutable;
use Nette\SmartObject;

/**
 * @property int                 $id
 * @property string|null         $type
 * @property int                 $unitId
 * @property int|null            $skautisId
 * @property string              $name
 * @property float|null          $defaultAmount
 * @property ChronosDate|null    $dueDate
 * @property int|null            $constantSymbol
 * @property VariableSymbol|null $nextVariableSymbol
 * @property string              $state
 * @property bool                $isRemindersEnabled
 * @property OAuthId|null        $oAuthId
 * @property string              $note
 */
class Group
{
    use SmartObject;

    /** @param int[] $unitIds */
    public function __construct(
        private int $id,
        private ?string $type,
        private array $unitIds,
        private ?int $skautisId,
        private string $name,
        private ?float $defaultAmount,
        private ?ChronosDate $dueDate,
        private ?int $constantSymbol,
        private ?VariableSymbol $nextVariableSymbol,
        private string $state,
        private ?OAuthId $oAuthId,
        private string $note,
        private ?int $bankAccountId,
        private bool $isRemindersEnabled = false,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): ?string
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

    public function getSkautisId(): ?int
    {
        return $this->skautisId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDefaultAmount(): ?float
    {
        return $this->defaultAmount;
    }

    public function getDueDate(): ?DateTimeImmutable
    {
        return $this->dueDate?->toNative();
    }

    public function getConstantSymbol(): ?int
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

    public function getOAuthId(): ?OAuthId
    {
        return $this->oAuthId;
    }

    public function getNote(): string
    {
        return $this->note;
    }

    public function getBankAccountId(): ?int
    {
        return $this->bankAccountId;
    }

    public function isRemindersEnabled(): bool
    {
        return $this->isRemindersEnabled;
    }
}
