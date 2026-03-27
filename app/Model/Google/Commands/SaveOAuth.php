<?php

declare(strict_types=1);

namespace App\Model\Google\Commands;

use App\Model\Common\UnitId;
use App\Model\Google\Handlers\SaveOAuthHandler;

/** @see SaveOAuthHandler */
final class SaveOAuth
{
    public function __construct(private string $code, private UnitId $unitId)
    {
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getUnitId(): UnitId
    {
        return $this->unitId;
    }
}
