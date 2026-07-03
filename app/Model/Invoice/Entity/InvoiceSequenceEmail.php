<?php

declare(strict_types=1);

namespace App\Model\Invoice\Entity;

use App\Model\Infrastructure\Entity\AbstractIdEntity;
use App\Model\Invoice\EmailTemplate;
use App\Model\Invoice\EmailType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

#[Entity]
#[Table(name: 'invoice_sequence_email')]
#[UniqueConstraint(name: 'invoice_sequence_email_type_unique', columns: ['sequence_id', 'type'])]
class InvoiceSequenceEmail extends AbstractIdEntity
{
    #[ManyToOne(targetEntity: InvoiceSequence::class, inversedBy: 'emails')]
    #[JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private InvoiceSequence $sequence;

    #[Column(type: Types::STRING, length: 50)]
    private string $type;

    #[Column(type: Types::BOOLEAN)]
    private bool $enabled = true;

    #[Column(type: Types::STRING, length: 255)]
    private string $subject;

    #[Column(type: Types::TEXT)]
    private string $body;

    public function __construct(InvoiceSequence $sequence, EmailType $type, EmailTemplate $template)
    {
        $this->sequence = $sequence;
        $this->type = $type->toString();
        $this->subject = $template->getSubject();
        $this->body = $template->getBody();
    }

    public function updateTemplate(EmailTemplate $template): void
    {
        $this->subject = $template->getSubject();
        $this->body = $template->getBody();
        $this->enabled = true;
    }

    public function disable(): void
    {
        $this->enabled = false;
    }

    public function getSequence(): InvoiceSequence
    {
        return $this->sequence;
    }

    public function getType(): EmailType
    {
        return EmailType::get($this->type);
    }

    public function getTemplate(): EmailTemplate
    {
        return new EmailTemplate($this->subject, $this->body);
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
