<?php

declare(strict_types=1);

namespace Model\Participant;

use Cake\Chronos\Date;

class Participant
{
    private string|null $nickName = null;

    public function __construct(
        private int $id,
        private int $personId,
        private string $firstName,
        private string $lastName,
        string|null $nickname,
        private int|null $age = null,
        private Date|null $birthday = null,
        private string $street,
        private string $city,
        private int $postcode,
        private string $state,
        private int|null $unitId = null,
        private string $unit,
        private string $unitRegistrationNumber,
        private int $days,
        private bool $isAccepted,
        private Payment $payment,
        private string|null $category = null,
    ) {
        $this->nickName = $nickname;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPersonId(): int
    {
        return $this->personId;
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

    public function getAge(): int|null
    {
        return $this->age;
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

    public function getState(): string
    {
        return $this->state;
    }

    public function getUnitId(): int|null
    {
        return $this->unitId;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function getUnitRegistrationNumber(): string
    {
        return $this->unitRegistrationNumber;
    }

    public function getDays(): int
    {
        return $this->days;
    }

    public function getPayment(): Payment
    {
        return $this->payment;
    }

    public function getDisplayName(): string
    {
        return $this->lastName . ' ' . $this->firstName . ($this->nickName !== null ? '(' . $this->nickName . ')' : '');
    }

    public function getCategory(): string|null
    {
        return $this->category;
    }

    public function getIsAccepted(): bool
    {
        return $this->isAccepted;
    }
}
