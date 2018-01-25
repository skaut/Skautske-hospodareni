<?php

namespace Model;

use Dibi\Connection;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class BaseTable
{

    const TABLE_CHIT = 'ac_chits';
    const TABLE_CATEGORY = 'ac_chitsCategory';
    const TABLE_CATEGORY_OBJECT = 'ac_chitsCategory_object';
    const TABLE_CHIT_VIEW = 'ac_chitsView';
    const TABLE_CAMP_PARTICIPANT = 'ac_camp_participants';
    const TABLE_OBJECT = 'ac_object';
    const TABLE_OBJECT_TYPE = 'ac_object_type';
    const TABLE_UNIT_BUDGET_CATEGORY = 'ac_unit_budget_category';
    const TABLE_TC_COMMANDS = 'tc_commands';
    const TABLE_TC_COMMAND_TYPES = 'tc_command_types';
    const TABLE_TC_TRAVEL_TYPES = 'tc_travelTypes';
    const TABLE_TC_VEHICLE = 'tc_vehicle';

    /** @var Connection */
    protected $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getByEventId($skautisEventId, $type)
    {
        return $this->connection->fetch("SELECT id as localId, prefix FROM  [" . self::TABLE_OBJECT . "] WHERE skautisId=%i AND type=%s LIMIT 1", $skautisEventId, $type);
    }

}
