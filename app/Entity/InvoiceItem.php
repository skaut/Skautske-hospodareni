<?php

declare(strict_types=1);

namespace Entity;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
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

    #[Column(type: 'big_decimal', precision: 15, scale: 2, nullable: true)]
    private ?BigDecimal $vat;

    public function __construct(BigDecimal $price, string $purpose, int $quantity = 1, string $unit = 'ks', ?BigDecimal $vat = null)
    {
        $this->price = $price;
        $this->purpose = $purpose;
        $this->quantity = $quantity;
        $this->unit = $unit;
        $this->vat = $vat;
    }

    public static function fromForm(mixed $item): self
    {
        return new self(
            BigDecimal::of($item['price']),
            $item['purpose'],
            $item['quantity'],
            $item['unit'],
            isset($item['vat']) ? BigDecimal::of($item['vat']) : null);
    }

    public function getPriceBase(): BigDecimal
    {
        return $this->getTotalPrice()->dividedBy($this->getVatFraction(), 2, RoundingMode::DOWN);
    }

    public function getPriceVat(): BigDecimal
    {
        $priceWithoutVat = $this->getPriceBase();

        return $this->getTotalPrice()->minus($priceWithoutVat);
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

    public function getVat(): BigDecimal
    {
        return $this->vat ?? BigDecimal::of(0);
    }

    public function getVatFraction(): BigDecimal
    {
        return $this->vat->dividedBy(100)->plus(1);
    }

    public function setVat(BigDecimal $vat): void
    {
        $this->vat = $vat;
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
