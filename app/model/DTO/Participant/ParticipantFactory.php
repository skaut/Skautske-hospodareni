<?php

declare(strict_types=1);

namespace Model\DTO\Payment;

use Model\DTO\Participant\Participant as ParticipantDTO;
use Model\Participant\Participant;
use Nette\StaticClass;

final class ParticipantFactory
{
    use StaticClass;

    public static function create(Participant $participant) : ParticipantDTO
    {
        return new ParticipantDTO(
            $participant->getId(),
            $participant->getPersonId(),
            $participant->getFirstName(),
            $participant->getLastName(),
            $participant->getNickName(),
            $participant->getAge(),
            $participant->getBirthday(),
            $participant->getStreet(),
            $participant->getCity(),
            $participant->getPostcode(),
            $participant->getStreet(),
            $participant->getUnit(),
            $participant->getUnitRegistrationNumber(),
            $participant->getDays(),
            $participant->getPayment(),
            $participant->getCategory()
        );
    }
}
