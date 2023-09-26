<?php

declare(strict_types=1);

namespace Model\DTO\Instructor;

use Model\Participant\Payment;
use Model\Utils\MoneyFactory;
use Nette\SmartObject;

/**
 * @property-read int $id
 * @property-read int $personId
 * @property-read string $firstName
 * @property-read string $lastName
 * @property-read string $nickname
 * @property-read string $displayName
 * @property-read int $educationId
 * @property-read string $educationName
 * @property-read string $instructorType
 * @property-read string $scoutExperience
 * @property-read string $professionalExperience
 * @property-read string $eventFocus
 * @property-read float $payment
 * @property-read float $repayment
 * @property-read string $onAccount
 */
class Instructor
{
    use SmartObject;

    private Payment $paymentObj;

    public function __construct(
        private int $id,
        private int $personId,
        private string $firstName,
        private string $lastName,
        private string|null $nickname = null,
        private int $educationId,
        private string $educationName,
        private string $instructorType,
        private string $scoutExperience,
        private string $professionalExperience,
        private string $eventFocus,
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

    public function getNickname(): string|null
    {
        return $this->nickname;
    }

    public function getEducationId(): int
    {
        return $this->educationId;
    }

    public function getEducationName(): string
    {
        return $this->educationName;
    }

    public function getInstructorType(): string
    {
        return $this->instructorType;
    }

    public function getScoutExperience(): string
    {
        return $this->scoutExperience;
    }

    public function getProfessionalExperience(): string
    {
        return $this->professionalExperience;
    }

    public function getEventFocus(): string
    {
        return $this->eventFocus;
    }

    public function getDisplayName(): string
    {
        return $this->lastName . ' ' . $this->firstName . ($this->nickname !== null ? '(' . $this->nickname . ')' : '');
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
