<?php

declare(strict_types=1);

namespace Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Enum\InvoiceSequenceState;
use Model\Common\UnitId;
use Model\Google\OAuthId;
use Model\Infrastructure\DoctrineNullableEmbeddables\Nullable;
use Model\Payment\Group\BankAccount;
use Nette\Utils\ArrayHash;
use Repository\InvoiceSequenceRepository;

#[ORM\Entity(repositoryClass: InvoiceSequenceRepository::class)]
#[ORM\Table(name: 'invoice_sequence')]
class InvoiceSequence extends AbstractIdEntity
{
    #[ORM\Column(type: Types::INTEGER)]
    private int $unit;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $sequence;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private int $year;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $description;

    #[ORM\Embedded(class: BankAccount::class, columnPrefix: false)]
    #[Nullable]
    private BankAccount|null $bankAccount = null;

    #[ORM\Column(type: 'oauth_id', nullable: true)]
    private OAuthId|null $oauthId;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private int|null $defaultDueDate = null;

    #[ORM\Column(type: 'string_enum', length: 20)]
    private string $state = InvoiceSequenceState::OPEN;

    public function __construct(int $unit, string $sequence, int $year, string $description, BankAccount|null $bankAccount = null, OAuthId|null $oauthId = null, int|null $defaultDueDate = null)
    {
        $this->unit           = $unit;
        $this->sequence       = $sequence;
        $this->year           = $year;
        $this->description    = $description;
        $this->bankAccount    = $bankAccount;
        $this->oauthId        = $oauthId;
        $this->defaultDueDate = $defaultDueDate;
    }

    public static function fromForm(UnitId $unit, ArrayHash $values): self
    {
        return new self(
            $unit->toInt(),
            $values->sequence,
            (int) $values->year,
            $values->description,
            $values->bankAccount,
            OAuthId::fromStringOrNull($values->oAuthId),
            $values->defaultDueDate,
        );
    }

    public function getSequence(): string
    {
        return $this->sequence;
    }

    public function setSequence(string $sequence): void
    {
        $this->sequence = $sequence;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function setYear(int $year): void
    {
        $this->year = $year;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getBankAccount(): BankAccount|null
    {
        return $this->bankAccount;
    }

    public function setBankAccount(BankAccount|null $bankAccount): void
    {
        $this->bankAccount = $bankAccount;
    }

    public function getOauthId(): OAuthId|null
    {
        return $this->oauthId;
    }

    public function setOauthId(OAuthId|null $oauthId): void
    {
        $this->oauthId = $oauthId;
    }

    public function getDefaultDueDate(): int|null
    {
        return $this->defaultDueDate;
    }

    public function setDefaultDueDate(int|null $defaultDueDate): void
    {
        $this->defaultDueDate = $defaultDueDate;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getUnit(): int
    {
        return $this->unit;
    }

    public function setUnit(int $unit): void
    {
        $this->unit = $unit;
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'unit' => $this->unit,
            'sequence' => $this->sequence,
            'year' => $this->year,
            'description' => $this->description,
            'bankAccount' => $this->bankAccount,
            'oauthId' => $this->oauthId,
            'defaultDueDate' => $this->defaultDueDate,
            'state' => $this->state,
        ];
    }
}
