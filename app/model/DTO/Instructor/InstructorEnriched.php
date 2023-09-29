<?php

declare(strict_types=1);

namespace Model\DTO\Instructor;

use Cake\Chronos\Date;

/**
 * @property-read string $street
 * @property-read string $city
 * @property-read string $postcode
 * @property-read Date|null $birthday
 */
class InstructorEnriched extends Instructor
{
    public function __construct(
        Instructor $instructor,
        private string $street,
        private string $city,
        private string $postcode,
        private Date|null $birthday,
    ) {
        parent::__construct(
            $instructor->getId(),
            $instructor->getPersonId(),
            $instructor->getFirstName(),
            $instructor->getLastName(),
            $instructor->getNickName(),
            $instructor->getEducationId(),
            $instructor->getEducationName(),
            $instructor->getInstructorType(),
            $instructor->getScoutExperience(),
            $instructor->getProfessionalExperience(),
            $instructor->getEventFocus(),
            $instructor->getPaymentObject(),
        );
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getPostcode(): string
    {
        return $this->postcode;
    }

    public function getBirthday(): Date|null
    {
        return $this->birthday;
    }
}
