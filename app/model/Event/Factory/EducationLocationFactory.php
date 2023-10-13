<?php

declare(strict_types=1);

namespace Model\Skautis\Factory;

use Model\Event\EducationLocation;
use Model\Event\SkautisEducationLocationId;
use stdClass;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

final class EducationLocationFactory
{
    public function create(stdClass $skautisEducationLocation): EducationLocation
    {
        return new EducationLocation(
            new SkautisEducationLocationId($skautisEducationLocation->ID),
            $skautisEducationLocation->FirstLine,
            $skautisEducationLocation->DisplayName,
        );
    }
}
