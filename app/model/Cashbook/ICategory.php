<?php

declare(strict_types=1);

namespace Model\Cashbook;

interface ICategory
{
    public const UNDEFINED_INCOME_ID  = 12;
    public const UNDEFINED_EXPENSE_ID = 8;
    public const CAMP_RESERVE_ID      = 15;

    public function getId() : int;

    public function getName() : string;

    public function getShortcut() : string;

    public function getOperationType() : Operation;

    public function isVirtual() : bool;
}
