<?php

declare(strict_types=1);

namespace Stubs;

use App\Model\Google\OAuthId;
use App\Model\Payment\Services\IOAuthAccessChecker;

final class OAuthsAccessCheckerStub implements IOAuthAccessChecker
{
    /** @param int[] $unitIds */
    public function allUnitsHaveAccessToOAuth(array $unitIds, OAuthId $oAuthId): bool
    {
        return true;
    }
}
