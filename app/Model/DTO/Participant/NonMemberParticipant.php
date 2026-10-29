<?php

declare(strict_types=1);

namespace App\Model\DTO\Participant;

use Cake\Chronos\ChronosDate;

class NonMemberParticipant
{
    public const MISSING_SEX = 'sex';
    public const MISSING_ADDRESS = 'address';

    public function __construct(
        private string $firstName,
        private string $lastName,
        private ?string $nickName,
        private string $sex,
        private ?ChronosDate $birthday,
        private string $street,
        private string $city,
        private string $postcode,
    ) {
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getNickName(): ?string
    {
        return $this->nickName;
    }

    public function getSex(): string
    {
        return $this->sex;
    }

    public function getBirthday(): ?ChronosDate
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

    public function getPostcode(): string
    {
        return $this->postcode;
    }

    /**
     * Údaje povinné pro úpravu nečlena, které ve skautISu chybí.
     *
     * @return list<self::MISSING_*>
     */
    public function getMissingData(): array
    {
        $missing = [];

        if ($this->sex === '') {
            $missing[] = self::MISSING_SEX;
        }

        if ($this->street === '' || $this->city === '' || $this->postcode === '') {
            $missing[] = self::MISSING_ADDRESS;
        }

        return $missing;
    }

    public function equals(self $other): bool
    {
        return $this->firstName === $other->firstName
            && $this->lastName === $other->lastName
            && $this->nickName === $other->nickName
            && $this->sex === $other->sex
            && $this->birthday?->format('Y-m-d') === $other->birthday?->format('Y-m-d')
            && $this->street === $other->street
            && $this->city === $other->city
            && $this->postcode === $other->postcode;
    }
}
