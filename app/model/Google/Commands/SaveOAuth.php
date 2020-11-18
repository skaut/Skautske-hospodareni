<?php

declare(strict_types=1);

namespace Model\Google\Commands;

use Model\Common\UnitId;
use Model\Google\Handlers\SaveOAuthHandler;

/**
 * @see SaveOAuthHandler
 */
final class SaveOAuth
{
    /** @var string */
    private $code;

    /** @var UnitId */
    private $unitId;

    public function __construct(string $code, UnitId $unitId)
    {
        $this->code   = $code;
        $this->unitId = $unitId;
    }

    public function getCode() : string
    {
        return $this->code;
    }

    public function getUnitId() : UnitId
    {
        return $this->unitId;
    }
}
