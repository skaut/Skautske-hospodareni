<?php

declare(strict_types=1);

namespace Model\Invoice;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'invoice_unit')]
class InvoiceUnit
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'integer')]
    private int $unitId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 255)]
    private string $address;

    #[ORM\Column(type: 'string', length: 64)]
    private string $city;

    #[ORM\Column(type: 'string', length: 64)]
    private string $zipcode;

    #[ORM\Column(type: 'string', length: 64)]
    private string $companyNumber;

    #[ORM\Column(type: 'string', length: 64)]
    private string $vatNumber;

    #[ORM\Column(type: 'string', length: 255)]
    private string $registration;

    #[ORM\Column(type: 'boolean')]
    private bool $vatPayer;
}
