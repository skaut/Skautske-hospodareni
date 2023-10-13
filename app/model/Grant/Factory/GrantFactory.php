<?php

declare(strict_types=1);

namespace Model\Skautis\Factory;

use Model\Grant\Grant;
use Model\Grant\SkautisGrantId;
use Model\Utils\MoneyFactory;
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
            MoneyFactory::fromFloat((float) $skautisGrant->AmountMaxRealValid),
            (float) $skautisGrant->Ratio,
            MoneyFactory::fromFloat((float) $skautisGrant->RemainingPay),
        );
    }
}
