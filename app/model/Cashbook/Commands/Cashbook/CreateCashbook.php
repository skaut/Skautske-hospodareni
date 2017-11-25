<?php

namespace Model\Cashbook\Commands\Cashbook;

use Model\Cashbook\Handlers\Cashbook\CreateCashbookHandler;

/**
 * @see CreateCashbookHandler
 */
final class CreateCashbook
{

    /** @var int */
    private $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }

}
