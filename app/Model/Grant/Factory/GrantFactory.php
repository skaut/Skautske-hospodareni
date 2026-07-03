<?php

declare(strict_types=1);

namespace App\Model\Skautis\Factory;

use App\Model\Grant\Grant;
use App\Model\Grant\SkautisGrantId;
use App\Model\Utils\MoneyFactory;
use stdClass;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

final class GrantFactory
{
    public function create(stdClass $skautisGrant): Grant
    {
        return new Grant(
            new SkautisGrantId($skautisGrant->ID),
            $skautisGrant->ID_GrantState,
            MoneyFactory::fromFloat((float) $skautisGrant->AmountMaxValid),
            MoneyFactory::fromFloat((float) $skautisGrant->AmountRequested),
            (float) $skautisGrant->Ratio,
        );
    }
}
