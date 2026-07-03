<?php

declare(strict_types=1);

namespace App\Model\Invoice\Entity;

use App\Model\Infrastructure\Entity\AbstractIdEntity;
use Brick\Math\BigDecimal;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: 'invoice_item')]
class InvoiceItem extends AbstractIdEntity
{
    #[ManyToOne(targetEntity: Invoice::class, inversedBy: 'items')]
    #[JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Invoice $invoice;

    #[Column(type: Types::INTEGER, nullable: false)]
    private int $quantity;

    #[Column(type: Types::STRING, length: 10)]
    private string $unit;

    #[Column(type: Types::STRING, length: 255)]
    private string $purpose;

    #[Column(type: 'big_decimal', precision: 15, scale: 2, nullable: false)]
    private BigDecimal $price;

    public function __construct(BigDecimal $price, string $purpose, int $quantity = 1, string $unit = 'ks')
    {
        $this->price = $price;
        $this->purpose = $purpose;
        $this->quantity = $quantity;
        $this->unit = $unit;
    }

    public static function fromForm(mixed $item): self
    {
        return new self(
            BigDecimal::of($item['price']),
            $item['purpose'],
            $item['quantity'],
            $item['unit'],
        );
    }

    public function getTotalPrice(): BigDecimal
    {
        return $this->price->multipliedBy($this->quantity);
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function setUnit(string $unit): void
    {
        $this->unit = $unit;
    }

    public function getPurpose(): string
    {
        return $this->purpose;
    }

    public function setPurpose(string $purpose): void
    {
        $this->purpose = $purpose;
    }

    public function getPrice(): BigDecimal
    {
        return $this->price;
    }

    public function setPrice(BigDecimal $price): void
    {
        $this->price = $price;
    }

    public function getInvoice(): Invoice
    {
        return $this->invoice;
    }

    public function setInvoice(Invoice $invoice): void
    {
        $this->invoice = $invoice;
    }
}
