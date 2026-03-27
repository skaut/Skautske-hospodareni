<?php

declare(strict_types=1);

namespace App\Model\Payment\Services;

use App\Model\Google\Exception\OAuthNotFound;
use App\Model\Google\OAuthId;

interface IOAuthAccessChecker
{
    /**
     * @param int[] $unitIds
     *
     * @throws OAuthNotFound
     */
    public function allUnitsHaveAccessToOAuth(array $unitIds, OAuthId $oAuthId): bool;
}
