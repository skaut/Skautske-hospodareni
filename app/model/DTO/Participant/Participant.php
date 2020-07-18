<?php

declare(strict_types=1);

namespace Model\DTO\Participant;

use Cake\Chronos\Date;
use Model\Participant\Payment;
use Model\Utils\MoneyFactory;
use Nette\SmartObject;

/**
 * @property-read int $id
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
 */
class Participant
{
    use SmartObject;

    private int $id;

    private int $personId;

    private string $firstName;

    private string $lastName;

    private ?string $nickName = null;

    private ?int $age = null;

    private ?Date $birthday = null;

    private string $street;

    private string $city;

    private int $postcode;

    private string $state;

    private string $unit;

    private string $unitRegistrationNumber;

    private int $days;

    private Payment $paymentObj;

    private ?string $category = null;

    public function __construct(int $id, int $personId, string $firstName, string $lastName, ?string $nickName, ?int $age, ?Date $birthday, string $street, string $city, int $postcode, string $state, string $unit, string $unitRegistrationNumber, int $days, Payment $payment, ?string $category)
    {
        $this->id                     = $id;
        $this->personId               = $personId;
        $this->firstName              = $firstName;
        $this->lastName               = $lastName;
        $this->nickName               = $nickName;
        $this->age                    = $age;
        $this->birthday               = $birthday;
        $this->street                 = $street;
        $this->city                   = $city;
        $this->postcode               = $postcode;
        $this->state                  = $state;
        $this->unit                   = $unit;
        $this->unitRegistrationNumber = $unitRegistrationNumber;
        $this->days                   = $days;
        $this->paymentObj             = $payment;
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

    public function getUnitRegistrationNumber() : string
    {
        return $this->unitRegistrationNumber;
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

    public function getUnit() : string
    {
        return $this->unit;
    }

    public function getDisplayName() : string
    {
        return $this->lastName . ' ' . $this->firstName . ($this->nickName !== null ? '(' . $this->nickName . ')' : '');
    }

    public function getDays() : int
    {
        return $this->days;
    }

    public function getPayment() : float
    {
        return MoneyFactory::toFloat($this->paymentObj->getPayment());
    }

    public function getRepayment() : float
    {
        return MoneyFactory::toFloat($this->paymentObj->getRepayment());
    }

    public function getOnAccount() : string
    {
        return $this->paymentObj->getAccount();
    }

    public function getCategory() : string
    {
        return $this->category ?? '';
    }
}
