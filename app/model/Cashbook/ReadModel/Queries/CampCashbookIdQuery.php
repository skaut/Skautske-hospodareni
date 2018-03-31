<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Model\Cashbook\ReadModel\QueryHandlers\CampCashbookIdQueryHandler;
use Model\Event\SkautisCampId;

/**
 * @see CampCashbookIdQueryHandler
 */
final class CampCashbookIdQuery
{

    /** @var SkautisCampId */
    private $campId;

    public function __construct(SkautisCampId $campId)
    {
        $this->campId = $campId;
    }

    public function getCampId(): SkautisCampId
    {
        return $this->campId;
    }

}
