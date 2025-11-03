<?php

declare(strict_types=1);

namespace Model\DTO\Participant;

use Cake\Chronos\ChronosDate;
use Model\Participant\Payment;
use Model\Utils\MoneyFactory;
use Nette\SmartObject;

/**
 * @property int              $id
 * @property int              $personId
 * @property string           $firstName
 * @property string           $lastName
 * @property string           $nickName
 * @property int|null         $age
 * @property string           $displayName
 * @property string           $street
 * @property string           $city
 * @property int              $postcode
 * @property ChronosDate|null $birthday
 * @property string           $unitRegistrationNumber
 * @property float            $payment
 * @property float            $repayment
 * @property string           $onAccount
 * @property int              $days
 * @property bool             $isAccepted
 */
class Participant
{
    use SmartObject;

    private Payment $paymentObj;

    public function __construct(
        private int $id,
        private int $personId,
        private string $firstName,
        private string $lastName,
        private ?string $nickName,
        private ?int $age,
        private ?ChronosDate $birthday,
        private string $street,
        private string $city,
        private int $postcode,
        private string $state,
        private string $unit,
        private string $unitRegistrationNumber,
        private int $days,
        private bool $isAccepted,
        Payment $payment,
        private ?string $category,
    ) {
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

    public function getNickName(): ?string
    {
        return $this->nickName;
    }

    public function getAge(): ?int
    {
        return $this->age;
    }

    public function getBirthday(): ?ChronosDate
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
        return $this->lastName.' '.$this->firstName.($this->nickName !== null ? '('.$this->nickName.')' : '');
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
