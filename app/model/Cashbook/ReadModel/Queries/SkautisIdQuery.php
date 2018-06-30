<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\ReadModel\QueryHandlers\SkautisIdQueryHandler;

/**
 * @see SkautisIdQueryHandler
 */
final class SkautisIdQuery
{

    /** @var CashbookId */
    private $cashbookId;

    public function __construct(CashbookId $cashbookId)
    {
        $this->cashbookId = $cashbookId;
    }

    public function getCashbookId(): CashbookId
    {
        return $this->cashbookId;
    }

}
