<?php

declare(strict_types=1);

namespace Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'invoice_unit')]
class InvoiceUnit extends AbstractIdEntity
{
    #[ORM\Column(type: Types::INTEGER)]
    private int $unitId;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $name;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $address;

    #[ORM\Column(type: Types::STRING, length: 64)]
    private string $city;

    #[ORM\Column(type: Types::STRING, length: 64)]
    private string $zipcode;

    #[ORM\Column(type: Types::STRING, length: 64)]
    private string $companyNumber;

    #[ORM\Column(type: Types::STRING, length: 64)]
    private string $vatNumber;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $registration;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $vatPayer;
}
