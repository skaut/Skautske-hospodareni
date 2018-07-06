<?php

declare(strict_types=1);

namespace Model\Travel\Contract;

use Nette\SmartObject;

/**
 * @property-read string $name
 * @property-read string $contact
 * @property-read string $address
 * @property-read \DateTimeImmutable|NULL $birthday
 */
final class Passenger
{
    use SmartObject;

    /** @var string */
    private $name;

    /** @var string */
    private $contact;

    /** @var string */
    private $address;

    /** @var \DateTimeImmutable|NULL */
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

    public function getBirthday()
    {
        return $this->birthday;
    }
}
