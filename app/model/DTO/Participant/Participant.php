<?php

declare(strict_types=1);

namespace Model\DTO\Participant;

use Cake\Chronos\Date;
use Model\Participant\Payment;

/**
 * @property-read int|null $age
 * @property-read Date|null $birthday
 * @property-read string $unitRegistrationNumber
 * @property-read string $street
 * @property-read string $city
 * @property-read int $postcode
 * @property-read string $state
 * @property-read string $unit
 * @property-read int $days
 * @property-read bool $isAccepted
 * @property-read string $category
 */
class Participant extends ParticipatingPerson
{
    public function __construct(
        int $id,
        int $personId,
        string $firstName,
        string $lastName,
        string|null $nickName = null,
        private int|null $age = null,
        private Date|null $birthday = null,
        private string $street,
        private string $city,
        private int $postcode,
        private string $state,
        private string $unit,
        private string $unitRegistrationNumber,
        private int $days,
        private bool $isAccepted,
        Payment $payment,
        private string|null $category = null,
    ) {
        parent::__construct($id, $personId, $firstName, $lastName, $nickName, $payment);
    }

    public function getAge(): int|null
    {
        return $this->age;
    }

    public function getBirthday(): Date|null
    {
        return $this->birthday;
    }

    public function getUnitRegistrationNumber(): string
    {
        return $this->unitRegistrationNumber;
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

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function getDays(): int
    {
        return $this->days;
    }

    public function getCategory(): string
    {
        return $this->category ?? '';
    }

    public function isAccepted(): bool
    {
        return $this->isAccepted;
    }
}
