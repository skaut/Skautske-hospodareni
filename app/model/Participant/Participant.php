<?php

declare(strict_types=1);

namespace Model\Participant;

use Cake\Chronos\Date;

class Participant
{
    private int $id;

    private int $personId;

    private string $firstName;

    private string $lastName;

    /** @var string|null */
    private $nickName;

    /** @var int|null */
    private $age;

    /** @var Date|null */
    private $birthday;

    private string $street;

    private string $city;

    private int $postcode;

    private string $state;

    /** @var int|null */
    private $unitId;

    private string $unit;

    private string $unitRegistrationNumber;

    private int $days;

    private Payment $payment;

    /** @var string|null */
    private $category;

    public function __construct(
        int $id,
        int $personId,
        string $firstName,
        string $lastName,
        ?string $nickname,
        ?int $age,
        ?Date $birthday,
        string $street,
        string $city,
        int $postcode,
        string $state,
        ?int $unitId,
        string $unit,
        string $unitRegistrationNumber,
        int $days,
        Payment $payment,
        ?string $category
    ) {
        $this->id                     = $id;
        $this->personId               = $personId;
        $this->firstName              = $firstName;
        $this->lastName               = $lastName;
        $this->nickName               = $nickname;
        $this->age                    = $age;
        $this->birthday               = $birthday;
        $this->street                 = $street;
        $this->city                   = $city;
        $this->postcode               = $postcode;
        $this->state                  = $state;
        $this->unitId                 = $unitId;
        $this->unit                   = $unit;
        $this->unitRegistrationNumber = $unitRegistrationNumber;
        $this->days                   = $days;
        $this->payment                = $payment;
        $this->category               = $category;
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getPersonId() : int
    {
        return $this->personId;
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

    public function getAge() : ?int
    {
        return $this->age;
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

    public function getState() : string
    {
        return $this->state;
    }

    public function getUnitId() : ?int
    {
        return $this->unitId;
    }

    public function getUnit() : string
    {
        return $this->unit;
    }

    public function getUnitRegistrationNumber() : string
    {
        return $this->unitRegistrationNumber;
    }

    public function getDays() : int
    {
        return $this->days;
    }

    public function getPayment() : Payment
    {
        return $this->payment;
    }

    public function getDisplayName() : string
    {
        return $this->lastName . ' ' . $this->firstName . ($this->nickName !== null ? '(' . $this->nickName . ')' : '');
    }

    public function getCategory() : ?string
    {
        return $this->category;
    }
}
