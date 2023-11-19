<?php

declare(strict_types=1);

namespace Model\Travel\Contract;

use Cake\Chronos\ChronosDate;
use Doctrine\ORM\Mapping as ORM;
use Nette\SmartObject;

/**
 * @ORM\Embeddable()
 *
 * @property-read string $name
 * @property-read string $contact
 * @property-read string $address
 * @property-read ChronosDate|NULL $birthday
 */
final class Passenger
{
    use SmartObject;

    /** @ORM\Column(type="string", name="driver_name", length=64) */
    private string $name;

    /** @ORM\Column(type="string", name="driver_contact", length=64, nullable=true) */
    private string $contact;

    /** @ORM\Column(type="string", name="driver_address", length=64) */
    private string $address;

    /** @ORM\Column(type="chronos_date", nullable=true, name="driver_birthday") */
    private ChronosDate|null $birthday = null;

    public function __construct(string $name, string $contact, string $address, ChronosDate|null $birthday)
    {
        $this->name     = $name;
        $this->contact  = $contact;
        $this->address  = $address;
        $this->birthday = $birthday;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getContact(): string
    {
        return $this->contact;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getBirthday(): ChronosDate|null
    {
        return $this->birthday;
    }
}
