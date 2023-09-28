<?php

declare(strict_types=1);

namespace Model\DTO\Instructor;

use Model\DTO\Participant\ParticipatingPerson;
use Model\Participant\Payment;
use Model\Utils\MoneyFactory;

/**
 * @property-read int $personId
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
class Instructor extends ParticipatingPerson
{
    private Payment $paymentObj;

    public function __construct(
        int $id,
        private int $personId,
        string $firstName,
        string $lastName,
        string|null $nickname = null,
        private int $educationId,
        private string $educationName,
        private string $instructorType,
        private string $scoutExperience,
        private string $professionalExperience,
        private string $eventFocus,
        Payment $payment,
    ) {
        parent::__construct($id, $firstName, $lastName, $nickname);

        $this->paymentObj = $payment;
    }

    public function getPersonId(): int
    {
        return $this->personId;
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
