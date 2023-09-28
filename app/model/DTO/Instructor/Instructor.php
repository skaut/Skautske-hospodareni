<?php

declare(strict_types=1);

namespace Model\DTO\Instructor;

use Model\DTO\Participant\ParticipatingPerson;
use Model\Participant\Payment;

/**
 * @property-read int $educationId
 * @property-read string $educationName
 * @property-read string $instructorType
 * @property-read string $scoutExperience
 * @property-read string $professionalExperience
 * @property-read string $eventFocus
 */
class Instructor extends ParticipatingPerson
{
    public function __construct(
        int $id,
        int $personId,
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
        parent::__construct($id, $personId, $firstName, $lastName, $nickname, $payment);
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
}
