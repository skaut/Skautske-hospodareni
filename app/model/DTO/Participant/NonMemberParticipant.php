<?php

declare(strict_types=1);

namespace Model\DTO\Participant;

use Cake\Chronos\Date;

class NonMemberParticipant
{
    private string $firstName;

    private string $lastName;

    private ?string $nickName = null;

    private ?Date $birthday = null;

    private string $street;

    private string $city;

    private int $postcode;

    public function __construct(string $firstName, string $lastName, ?string $nickName, ?Date $birthday, string $street, string $city, int $postcode)
    {
        $this->firstName = $firstName;
        $this->lastName  = $lastName;
        $this->nickName  = $nickName;
        $this->birthday  = $birthday;
        $this->street    = $street;
        $this->city      = $city;
        $this->postcode  = $postcode;
    }

    public function getFirstName() : string
    {
        return $this->firstName;
    }

    public function getLastName() : string
    {
        return $this->lastName;
    }

    public function getNickName() : ?string
    {
        return $this->nickName;
    }

    public function getBirthday() : ?Date
    {
        return $this->birthday;
    }

    public function getStreet() : string
    {
        return $this->street;
    }

    public function getCity() : string
    {
        return $this->city;
    }

    public function getPostcode() : int
    {
        return $this->postcode;
    }
}
