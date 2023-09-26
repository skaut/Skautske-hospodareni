<?php

declare(strict_types=1);

namespace Model\Instructor;

use Model\Participant\Payment;

class Instructor
{
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
        private Payment $payment,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPersonId(): int
    {
        return $this->personId;
    }

    public function getfirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getNickname(): string
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

    public function getPayment(): Payment
    {
        return $this->payment;
    }

    public function getDisplayName(): string
    {
        return $this->lastName . ' ' . $this->firstName . ($this->nickname !== null ? '(' . $this->nickname . ')' : '');
    }
}
