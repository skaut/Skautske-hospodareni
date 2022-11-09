<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Event\SkautisEducationId;

/** @see EducationParticipantBalanceQueryHandler */
final class EducationParticipantBalanceQuery
{
    private SkautisEducationId $educationId;

    private CashbookId $cashbookId;

    public function __construct(SkautisEducationId $educationId, CashbookId $cashbookId)
    {
        $this->educationId = $educationId;
        $this->cashbookId  = $cashbookId;
    }

    public function getEducationId(): SkautisEducationId
    {
        return $this->educationId;
    }

    public function getCashbookId(): CashbookId
    {
        return $this->cashbookId;
    }
}
