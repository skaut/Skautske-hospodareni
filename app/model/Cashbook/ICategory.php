<?php

declare(strict_types=1);

namespace Model\Cashbook;

interface ICategory
{
    public const UNDEFINED_INCOME_ID            = 12;
    public const UNDEFINED_EXPENSE_ID           = 8;
    public const CAMP_RESERVE_ID                = 15;
    public const CATEGORY_PARTICIPANT_INCOME_ID = 1;
    public const CATEGORY_HPD_ID                = 11;
    public const CATEGORY_REFUND_ID             = 20;
    public const CATEGORY_REFUND_CHILD_ID       = 21;
    public const CATEGORY_REFUND_ADULT_ID       = 22;
    public const CATEGORY_REFUND_PARTICIPANT_ID = 23;
    public const CATEGORY_REFUND_INSTRUCTOR_ID  = 24;

    public function getId(): int;

    public function getName(): string;

    public function getShortcut(): string;

    public function getOperationType(): Operation;

    public function isVirtual(): bool;
}
