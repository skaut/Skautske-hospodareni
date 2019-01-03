<?php

declare(strict_types=1);

namespace Model\Travel\Contract;

use Doctrine\ORM\Mapping as ORM;
use Nette\SmartObject;

/**
 * @ORM\Embeddable()
 * @property-read string $name
 * @property-read string $contact
 * @property-read string $address
 * @property-read \DateTimeImmutable|NULL $birthday
 */
final class Passenger
{
    use SmartObject;

    /**
     * @var string
     * @ORM\Column(type="string", name="driver_name", length=64)
     */
    private $name;

    /**
     * @var string
     * @ORM\Column(type="string", name="driver_contact", length=64, nullable=true)
     */
    private $contact;

    /**
     * @var string
     * @ORM\Column(type="string", name="driver_address", length=64)
     */
    private $address;

    /**
     * @var \DateTimeImmutable|NULL
     * @ORM\Column(type="datetime_immutable", nullable=true, name="driver_birthday")
     */
    private $birthday;

    public function __construct(string $name, string $contact, string $address, ?\DateTimeImmutable $birthday)
    {
        $this->name     = $name;
        $this->contact  = $contact;
        $this->address  = $address;
        $this->birthday = $birthday;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getContact() : string
    {
        return $this->contact;
    }

    public function getAddress() : string
    {
        return $this->address;
    }

    public function getBirthday() : ?\DateTimeImmutable
    {
        return $this->birthday;
    }
}
