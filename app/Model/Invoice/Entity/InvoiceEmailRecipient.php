<?php

declare(strict_types=1);

namespace App\Model\Invoice\Entity;

use App\Model\Common\EmailAddress;
use App\Model\Infrastructure\Entity\AbstractIdEntity;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: 'invoice_email_recipient')]
class InvoiceEmailRecipient extends AbstractIdEntity
{
    #[ManyToOne(targetEntity: Invoice::class, inversedBy: 'emailRecipients')]
    #[JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Invoice $invoice;

    #[Column(type: 'email_address', length: 255)]
    private EmailAddress $emailAddress;

    public function __construct(Invoice $invoice, EmailAddress $emailAddress)
    {
        $this->invoice = $invoice;
        $this->emailAddress = $emailAddress;
    }

    public function getInvoice(): Invoice
    {
        return $this->invoice;
    }

    public function getEmailAddress(): EmailAddress
    {
        return $this->emailAddress;
    }
}
