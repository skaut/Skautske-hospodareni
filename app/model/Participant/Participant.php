<?php

declare(strict_types=1);

namespace Model\Participant;

use Cake\Chronos\Date;
use Money\Money;

class Participant
{
    /** @var int */
    private $id;

    /** @var string */
    private $firstName;

    /** @var string */
    private $lastName;

    /** @var string|null */
    private $nickName;

    /** @var int|null */
    private $age;

    /** @var Date */
    private $birthday;

    /** @var string */
    private $street;

    /** @var string */
    private $city;

    /** @var int */
    private $postcode;

    /** @var string */
    private $state;

    /** @var int|null */
    private $unitId;

    /** @var string */
    private $unit;

    /** @var string */
    private $unitRegistrationNumber;

    /** @var int */
    private $days;

    /** @var Money|null */
    private $payment;

    public function __construct(
        int $id,
        string $firstName,
        string $lastName,
        ?string $nickname,
        ?int $age,
        Date $birthday,
        string $street,
        string $city,
        int $postcode,
        string $state,
        ?int $unitId,
        string $unit,
        string $unitRegistrationNumber,
        int $days,
        ?Money $payment
    ) {
        $this->id                     = $id;
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
    }

    public function getId() : int
    {
        return $this->id;
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

    public function getBirthday() : Date
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

    public function getPayment() : ?Money
    {
        return $this->payment;
    }

    public function getDisplayName() : string
    {
        return $this->lastName . ' ' . $this->firstName . ($this->nickName !== null ? '(' . $this->nickName . ')' : '');
    }
}
