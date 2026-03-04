<?php

declare(strict_types=1);

namespace Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OrderBy;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Enum\InvoiceSequenceState;
use Model\Common\UnitId;
use Nette\Utils\ArrayHash;
use Repository\InvoiceSequenceRepository;

#[Entity(repositoryClass: InvoiceSequenceRepository::class)]
#[Table(name: 'invoice_sequence', options: ['collate' => 'utf8mb4_czech_ci'])]
#[UniqueConstraint(name: 'invoice_sequence_id_unit_sequence_year_unique', columns: ['unit', 'sequence_id', 'year'])]
class InvoiceSequence extends AbstractIdEntity
{
    #[Column(type: Types::INTEGER)]
    private int $unit;

    #[Column(type: Types::INTEGER, nullable: false, options: ['default' => 1])]
    private int $sequenceId;

    #[Column(type: Types::STRING, length: 20)]
    private string $sequence;

    #[Column(type: Types::INTEGER, nullable: true)]
    private ?int $year;

    #[Column(type: Types::STRING, length: 255)]
    private string $description;

    #[ManyToOne(targetEntity: BankAccount::class, cascade: ['persist'])]
    #[JoinColumn(nullable: true)]
    private ?BankAccount $bankAccount;

    #[ManyToOne(targetEntity: GoogleOAuth::class, cascade: ['persist'])]
    #[JoinColumn(nullable: true)]
    private ?GoogleOAuth $oauth;

    #[Column(type: Types::INTEGER, nullable: true)]
    private ?int $defaultDueDate;

    #[Column(type: 'string_enum', length: 20)]
    private string $state = InvoiceSequenceState::OPEN;

    #[Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $phone;

    #[Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isVatPayer = false;

    #[Column(type: Types::STRING, length: 20, nullable: true)]
    private ?string $vatNumber;

    /** @var Collection<int, Invoice>&iterable<Invoice> */
    #[OneToMany(mappedBy: 'sequence', targetEntity: Invoice::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[OrderBy(['dueDate' => 'ASC'])]
    private Collection $invoices;

    public function __construct(int $unit, string $sequence, int $year, string $description, ?BankAccount $bankAccount = null, ?GoogleOAuth $oauth = null, ?int $defaultDueDate = null, bool $isVatPayer = false)
    {
        $this->unit = $unit;
        $this->sequence = $sequence;
        $this->year = $year;
        $this->description = $description;
        $this->bankAccount = $bankAccount;
        $this->oauth = $oauth;
        $this->defaultDueDate = $defaultDueDate;
        $this->isVatPayer = $isVatPayer;
        $this->invoices = new ArrayCollection();
    }

    public static function fromForm(UnitId $unit, ArrayHash $values, ?BankAccount $bankAccount, ?GoogleOAuth $googleOAuth): self
    {
        $invoiceSequence = new self(
            $unit->toInt(),
            $values->sequence,
            (int) $values->year,
            $values->description,
            $bankAccount,
            $googleOAuth,
            $values->defaultDueDate,
            $values->supplier->isVatPayer,
        );

        $invoiceSequence->setPhone($values->supplier->phone);

        if ($values->supplier->vatNumber) {
            $invoiceSequence->setVatNumber($values->supplier->vatNumber);
        }

        return $invoiceSequence;
    }

    public function getSequence(): string
    {
        return $this->sequence;
    }

    public function setSequence(string $sequence): void
    {
        $this->sequence = $sequence;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(?int $year): void
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

    public function getBankAccount(): ?BankAccount
    {
        return $this->bankAccount;
    }

    public function setBankAccount(?BankAccount $bankAccount): void
    {
        $this->bankAccount = $bankAccount;
    }

    public function getOauth(): ?GoogleOAuth
    {
        return $this->oauth;
    }

    public function setOauth(?GoogleOAuth $oauth): void
    {
        $this->oauth = $oauth;
    }

    public function getDefaultDueDate(): ?int
    {
        return $this->defaultDueDate;
    }

    public function setDefaultDueDate(?int $defaultDueDate): void
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

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): void
    {
        $this->phone = $phone;
    }

    public function isVatPayer(): bool
    {
        return $this->isVatPayer;
    }

    public function setIsVatPayer(bool $isVatPayer): void
    {
        $this->isVatPayer = $isVatPayer;
    }

    public function getVatNumber(): ?string
    {
        return $this->vatNumber;
    }

    public function setVatNumber(?string $vatNumber): void
    {
        $this->vatNumber = $vatNumber;
    }

    /** @return Collection<int, Invoice> */
    public function getInvoices(): Collection
    {
        return $this->invoices;
    }

    /** @param Collection<int, Invoice> $invoices */
    public function setInvoices(Collection $invoices): void
    {
        $this->invoices = $invoices;
    }

    public function getSequenceId(): int
    {
        return $this->sequenceId;
    }

    public function setSequenceId(int $sequenceId): void
    {
        $this->sequenceId = $sequenceId;
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
            'googleOAuth' => $this->oauth,
            'defaultDueDate' => $this->defaultDueDate,
            'state' => $this->state,
        ];
    }
}
