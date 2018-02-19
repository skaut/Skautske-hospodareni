<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Model\Cashbook\ReadModel\QueryHandlers\ChitQueryHandler;

/**
 * @see ChitQueryHandler
 */
final class ChitQuery
{

    /** @var int */
    private $cashbookId;

    /** @var int */
    private $chitId;

    public function __construct(int $cashbookId, int $chitId)
    {
        $this->cashbookId = $cashbookId;
        $this->chitId = $chitId;
    }

    public function getCashbookId(): int
    {
        return $this->cashbookId;
    }

    public function getChitId(): int
    {
        return $this->chitId;
    }

}
