<?php

declare(strict_types=1);

namespace Stubs;

use Model\Google\OAuthId;
use Model\Payment\Services\IOAuthAccessChecker;

final class OAuthsAccessCheckerStub implements IOAuthAccessChecker
{
    /**
     * @param int[] $unitIds
     */
    public function allUnitsHaveAccessToOAuth(array $unitIds, OAuthId $oAuthId) : bool
    {
        return true;
    }
}
