<?php

declare(strict_types=1);

namespace Model;

use Dibi\Connection;

class BaseTable
{
    public const TABLE_CHIT                 = 'ac_chits';
    public const TABLE_CATEGORY             = 'ac_chitsCategory';
    public const TABLE_CATEGORY_OBJECT      = 'ac_chitsCategory_object';
    public const TABLE_CHIT_VIEW            = 'ac_chitsView';
    public const TABLE_OBJECT               = 'ac_object';
    public const TABLE_OBJECT_TYPE          = 'ac_object_type';
    public const TABLE_UNIT_BUDGET_CATEGORY = 'ac_unit_budget_category';
    public const TABLE_TC_COMMANDS          = 'tc_commands';
    public const TABLE_TC_COMMAND_TYPES     = 'tc_command_types';
    public const TABLE_TC_TRAVEL_TYPES      = 'tc_travelTypes';
    public const TABLE_TC_VEHICLE           = 'tc_vehicle';

    /** @var Connection */
    protected $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }
}
