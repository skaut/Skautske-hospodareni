<?php

declare(strict_types=1);

namespace App\Model\Invoice\Entity;

use App\Model\Infrastructure\Entity\AbstractIdEntity;
use App\Model\Invoice\EmailType;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: 'invoice_sent_email')]
class InvoiceSentEmail extends AbstractIdEntity
{
    #[ManyToOne(targetEntity: Invoice::class, inversedBy: 'sentEmails')]
    #[JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Invoice $invoice;

    #[Column(type: Types::STRING, length: 50)]
    private string $type;

    #[Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $time;

    #[Column(type: Types::STRING, length: 255)]
    private string $senderName;

    #[Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $successful = true;

    #[Column(type: Types::TEXT, nullable: true)]
    private ?string $errorMessage = null;

    public function __construct(Invoice $invoice, EmailType $type, DateTimeImmutable $time, string $senderName, bool $successful = true, ?string $errorMessage = null)
    {
        $this->invoice = $invoice;
        $this->type = $type->toString();
        $this->time = $time;
        $this->senderName = $senderName;
        $this->successful = $successful;
        $this->errorMessage = $errorMessage;
    }

    public function getType(): EmailType
    {
        return EmailType::get($this->type);
    }

    public function getTime(): DateTimeImmutable
    {
        return $this->time;
    }

    public function getSenderName(): string
    {
        return $this->senderName;
    }

    public function wasSuccessful(): bool
    {
        return $this->successful;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }
}
