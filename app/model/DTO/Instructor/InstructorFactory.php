<?php

declare(strict_types=1);

namespace Model\DTO\Payment;

use Model\DTO\Instructor\Instructor as InstructorDTO;
use Model\Instructor\Instructor;
use Nette\StaticClass;

final class InstructorFactory
{
    use StaticClass;

    public static function create(Instructor $participant): InstructorDTO
    {
        return new InstructorDTO(
            $participant->getId(),
            $participant->getPersonId(),
            $participant->getFirstName(),
            $participant->getLastName(),
            $participant->getNickName(),
            $participant->getEducationId(),
            $participant->getEducationName(),
            $participant->getInstructorType(),
            $participant->getScoutExperience(),
            $participant->getProfessionalExperience(),
            $participant->getEventFocus(),
            $participant->getPayment(),
        );
    }
}
