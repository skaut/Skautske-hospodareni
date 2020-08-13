<?php

declare(strict_types=1);

namespace Model\Payment\Services;

use Model\Google\Exception\OAuthNotFound;
use Model\Google\OAuthId;

interface IOAuthAccessChecker
{
    /**
     * @param int[] $unitIds
     *
     * @throws OAuthNotFound
     */
    public function allUnitsHaveAccessToOAuth(array $unitIds, OAuthId $oAuthId) : bool;
}
