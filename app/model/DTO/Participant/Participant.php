<?php

declare(strict_types=1);

namespace Model\DTO\Participant;

use Cake\Chronos\Date;
use Model\Participant\Payment;
use Model\Utils\MoneyFactory;
use Nette\SmartObject;

/**
 * @property-read int $id
 * @property-read int $personId
 * @property-read string $firstName
 * @property-read string $lastName
 * @property-read string $nickName
 * @property-read int|null $age
 * @property-read string $displayName
 * @property-read string $street
 * @property-read string $city
 * @property-read int $postcode
 * @property-read Date|null $birthday
 * @property-read string $unitRegistrationNumber
 * @property-read float $payment
 * @property-read float $repayment
 * @property-read string $onAccount
 * @property-read int $days
 * @property-read bool $isAccepted
 */
class Participant
{
    use SmartObject;

    private Payment $paymentObj;

    public function __construct(private int $id, private int $personId, private string $firstName, private string $lastName, private string|null $nickName = null, private int|null $age = null, private Date|null $birthday = null, private string $street, private string $city, private int $postcode, private string $state, private string $unit, private string $unitRegistrationNumber, private int $days, private bool $isAccepted, Payment $payment, private string|null $category = null)
    {
        $this->paymentObj = $payment;
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

    public function getDisplayName(): string
    {
        return $this->lastName . ' ' . $this->firstName . ($this->nickName !== null ? '(' . $this->nickName . ')' : '');
    }

    public function getDays(): int
    {
        return $this->days;
    }

    public function getPayment(): float
    {
        return MoneyFactory::toFloat($this->paymentObj->getPayment());
    }

    public function getRepayment(): float
    {
        return MoneyFactory::toFloat($this->paymentObj->getRepayment());
    }

    public function getOnAccount(): string
    {
        return $this->paymentObj->getAccount();
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
