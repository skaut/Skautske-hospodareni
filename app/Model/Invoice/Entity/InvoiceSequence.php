<?php

declare(strict_types=1);

namespace App\Model\Invoice\Entity;

use App\Model\Bank\Entity\BankAccount;
use App\Model\Common\UnitId;
use App\Model\Google\OAuthId;
use App\Model\Infrastructure\Entity\AbstractIdEntity;
use App\Model\Invoice\EmailTemplate;
use App\Model\Invoice\EmailType;
use App\Model\Invoice\Enum\InvoiceSequenceState;
use App\Model\Invoice\Repository\InvoiceSequenceRepository;
use App\Model\Payment\VariableSymbol;
use DateTimeImmutable;
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
use Nette\Utils\ArrayHash;

use function assert;
use function ltrim;
use function preg_match;
use function sprintf;
use function strlen;
use function trim;

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

    #[Column(type: Types::STRING, length: 10, options: ['default' => '00001'])]
    private string $firstNumber = '00001';

    #[Column(type: Types::INTEGER, nullable: true)]
    private ?int $year;

    #[Column(type: Types::STRING, length: 255)]
    private string $description;

    #[ManyToOne(targetEntity: BankAccount::class, cascade: ['persist'])]
    #[JoinColumn(nullable: true)]
    private ?BankAccount $bankAccount;

    #[Column(type: 'oauth_id', nullable: true)]
    private ?OAuthId $oauthId;

    #[Column(type: Types::INTEGER, nullable: true)]
    private ?int $defaultDueDate;

    #[Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $automaticPairingEnabled = false;

    #[Column(type: Types::INTEGER, nullable: true)]
    private ?int $pairingDaysBack = null;

    #[Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $lastPairing = null;

    #[Column(type: 'string_enum', length: 20)]
    private string $state = InvoiceSequenceState::OPEN;

    #[Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $phone;

    /** @var Collection<int, Invoice>&iterable<Invoice> */
    #[OneToMany(mappedBy: 'sequence', targetEntity: Invoice::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[OrderBy(['dueDate' => 'ASC'])]
    private Collection $invoices;

    /** @var Collection<int, InvoiceSequenceEmail>&iterable<InvoiceSequenceEmail> */
    #[OneToMany(mappedBy: 'sequence', targetEntity: InvoiceSequenceEmail::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $emails;

    public function __construct(int $unit, string $sequence, int $year, string $description, ?BankAccount $bankAccount = null, ?OAuthId $oauthId = null, ?int $defaultDueDate = null, string $firstNumber = '00001')
    {
        $this->unit = $unit;
        $this->sequence = $sequence;
        $this->firstNumber = $firstNumber;
        $this->year = $year;
        $this->description = $description;
        $this->bankAccount = $bankAccount;
        $this->oauthId = $oauthId;
        $this->defaultDueDate = $defaultDueDate;
        $this->invoices = new ArrayCollection();
        $this->emails = new ArrayCollection();
    }

    public static function fromForm(UnitId $unit, ArrayHash $values, ?BankAccount $bankAccount, ?OAuthId $oauthId): self
    {
        return new self(
            $unit->toInt(),
            $values->sequence,
            (int) $values->year,
            $values->description,
            $bankAccount,
            $oauthId,
            $values->defaultDueDate,
            $values->firstNumber,
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

    public function getFirstNumber(): string
    {
        return $this->firstNumber;
    }

    public function setFirstNumber(string $firstNumber): void
    {
        $this->firstNumber = $firstNumber;
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
        $currentBankAccountId = $this->bankAccount?->getId();
        $nextBankAccountId = $bankAccount?->getId();

        if ($currentBankAccountId !== $nextBankAccountId) {
            $this->invalidateLastPairing();
        }

        $this->bankAccount = $bankAccount;
    }

    public function getOauthId(): ?OAuthId
    {
        return $this->oauthId;
    }

    public function setOauthId(?OAuthId $oauthId): void
    {
        $this->oauthId = $oauthId;
    }

    public function getDefaultDueDate(): ?int
    {
        return $this->defaultDueDate;
    }

    public function setDefaultDueDate(?int $defaultDueDate): void
    {
        $this->defaultDueDate = $defaultDueDate;
    }

    public function isAutomaticPairingEnabled(): bool
    {
        return $this->automaticPairingEnabled;
    }

    public function setAutomaticPairingEnabled(bool $automaticPairingEnabled): void
    {
        $this->automaticPairingEnabled = $automaticPairingEnabled;
    }

    public function getPairingDaysBack(): ?int
    {
        return $this->pairingDaysBack;
    }

    public function setPairingDaysBack(?int $pairingDaysBack): void
    {
        $this->pairingDaysBack = $pairingDaysBack;
    }

    public function getLastPairing(): ?DateTimeImmutable
    {
        return $this->lastPairing;
    }

    public function updateLastPairing(DateTimeImmutable $lastPairing): void
    {
        $this->lastPairing = $lastPairing;
    }

    public function invalidateLastPairing(): void
    {
        $this->lastPairing = null;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function isOpen(): bool
    {
        return $this->state === InvoiceSequenceState::OPEN;
    }

    public function isClosed(): bool
    {
        return $this->state === InvoiceSequenceState::CLOSED;
    }

    public function close(): void
    {
        $this->state = InvoiceSequenceState::CLOSED;
    }

    public function reopen(): void
    {
        $this->state = InvoiceSequenceState::OPEN;
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

    public function getFirstNumberValue(): int
    {
        return (int) $this->firstNumber;
    }

    public function getNumberLength(): int
    {
        return strlen($this->firstNumber);
    }

    public function getNumericPrefix(): string
    {
        if (preg_match('/\d+$/', $this->sequence, $matches) !== 1) {
            return '';
        }

        return $matches[0];
    }

    public function getDisplayLabel(): string
    {
        $year = $this->year !== null ? (string) $this->year : 'bez roku';
        $sequence = trim($this->sequence);

        if ($sequence === '') {
            return 'rok '.$year.' bez prefixu';
        }

        return $sequence.'/'.$year;
    }

    public function getDisplayContextLabel(): string
    {
        $year = $this->year !== null ? (string) $this->year : 'bez roku';
        $sequence = trim($this->sequence);

        if ($sequence === '') {
            return 'řady pro rok '.$year.' bez prefixu';
        }

        return 'řady '.$sequence.'/'.$year;
    }

    public function formatInvoiceNumber(int $invoiceId): string
    {
        return $this->sequence.sprintf('%0'.$this->getNumberLength().'d', $invoiceId);
    }

    public function generateVariableSymbol(int $invoiceId): VariableSymbol
    {
        $numberPart = sprintf('%0'.$this->getNumberLength().'d', $invoiceId);
        $candidate = ltrim($this->getNumericPrefix().$numberPart, '0');

        return new VariableSymbol($candidate === '' ? (string) $invoiceId : $candidate);
    }

    public function getEmailTemplate(EmailType $type): ?EmailTemplate
    {
        $email = $this->getEmail($type);

        return $email?->getTemplate();
    }

    public function isEmailEnabled(EmailType $type): bool
    {
        $email = $this->getEmail($type);

        return $email !== null && $email->isEnabled();
    }

    public function updateEmail(EmailType $type, EmailTemplate $template): void
    {
        $email = $this->getEmail($type);

        if ($email !== null) {
            $email->updateTemplate($template);

            return;
        }

        $this->emails->add(new InvoiceSequenceEmail($this, $type, $template));
    }

    public function disableEmail(EmailType $type): void
    {
        $email = $this->getEmail($type);

        if ($email === null) {
            return;
        }

        $email->disable();
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'unit' => $this->unit,
            'sequence' => $this->sequence,
            'firstNumber' => $this->firstNumber,
            'year' => $this->year,
            'description' => $this->description,
            'bankAccount' => $this->bankAccount,
            'googleOAuth' => $this->oauthId,
            'defaultDueDate' => $this->defaultDueDate,
            'state' => $this->state,
        ];
    }

    private function getEmail(EmailType $type): ?InvoiceSequenceEmail
    {
        foreach ($this->emails as $email) {
            assert($email instanceof InvoiceSequenceEmail);

            if ($email->getType()->equals($type)) {
                return $email;
            }
        }

        return null;
    }
}
