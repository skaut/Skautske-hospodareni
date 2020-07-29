<?php

declare(strict_types=1);

namespace Model\Payment\Services;

use Model\Google\OAuthId;
use Model\Google\OAuthNotFound;

interface IOAuthAccessChecker
{
    /**
     * @param int[] $unitIds
     *
     * @throws OAuthNotFound
     */
    public function allUnitsHaveAccessToOAuth(array $unitIds, OAuthId $oAuthId) : bool;
}
