<?php

declare(strict_types=1);

namespace Model\Skautis\Factory;

use Model\Grant\Grant;
use Model\Grant\SkautisGrantId;
use stdClass;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

final class GrantFactory
{
    public function create(stdClass $skautisEducationGrant): Grant
    {
        return new Grant(
            new SkautisGrantId($skautisEducationGrant->ID),
        );
    }
}
