<?php

declare(strict_types=1);

namespace Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'invoice_item')]
class InvoiceItem extends AbstractIdEntity
{
    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    private int $quantity;

    #[ORM\Column(type: Types::STRING, length: 10)]
    private string $unit;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $name;

    #[ORM\Column(type: 'big_decimal', precision: 15, scale: 2, nullable: false)]
    private string $price;

    #[ORM\Column(type: 'big_decimal', precision: 15, scale: 2, nullable: true)]
    private string $vat;
}
