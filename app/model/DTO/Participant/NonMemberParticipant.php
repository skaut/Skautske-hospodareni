<?php

declare(strict_types=1);

namespace Model\DTO\Participant;

use Cake\Chronos\Date;

class NonMemberParticipant
{
    public function __construct(private string $firstName, private string $lastName, private string|null $nickName = null, private Date|null $birthday = null, private string $street, private string $city, private int $postcode)
    {
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getNickName(): string|null
    {
        return $this->nickName;
    }

    public function getBirthday(): Date|null
    {
        return $this->birthday;
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getPostcode(): int
    {
        return $this->postcode;
    }
}
