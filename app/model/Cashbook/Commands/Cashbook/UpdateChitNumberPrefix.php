<?php

declare(strict_types=1);

namespace Model\Cashbook\Commands\Cashbook;

use Model\Cashbook\Cashbook\CashbookId;

final class UpdateChitNumberPrefix
{
    /** @var CashbookId */
    private $cashbookId;

    /** @var string|NULL */
    private $prefix;

    public function __construct(CashbookId $cashbookId, ?string $prefix)
    {
        $this->cashbookId = $cashbookId;
        $this->prefix     = $prefix;
    }

    public function getCashbookId() : CashbookId
    {
        return $this->cashbookId;
    }

    public function getPrefix() : ?string
    {
        return $this->prefix;
    }
}
