<?php

declare(strict_types=1);

namespace Model\DTO\Participant;

use Model\Participant\Payment;
use Model\Utils\MoneyFactory;
use Nette\SmartObject;

/**
 * @property-read int $id
 * @property-read int $personId
 * @property-read string $firstName
 * @property-read string $lastName
 * @property-read string $nickName
 * @property-read string $displayName
 * @property-read float $payment
 * @property-read float $repayment
 * @property-read string $onAccount
 */
class ParticipatingPerson
{
    use SmartObject;

    public const PARTICIPANT = 'participant';
    public const INSTRUCTOR  = 'instructor';

    private Payment $paymentObj;

    public function __construct(
        private int $id,
        private int $personId,
        private string $firstName,
        private string $lastName,
        private string|null $nickName = null,
        Payment $payment,
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

    public function getNickName(): string|null
    {
        return $this->nickName;
    }

    public function getDisplayName(): string
    {
        return $this->lastName . ' ' . $this->firstName . ($this->nickName !== null ? '(' . $this->nickName . ')' : '');
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
}
