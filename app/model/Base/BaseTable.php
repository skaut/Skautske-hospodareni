<?php

namespace Model;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class BaseTable {

    const TABLE_CHIT = 'ac_chits';
    const TABLE_CATEGORY = 'ac_chitsCategory';
    const TABLE_CHIT_VIEW = 'ac_chitsView';
    const TABLE_CAMP_PARTICIPANT = 'ac_camp_participants';
    const TABLE_OBJECT = 'ac_object';
    const TABLE_OBJECT_TYPE = 'ac_object_type';
    const TABLE_UNIT_BUDGET_CATEGORY = 'ac_unit_budget_category';
    const TABLE_TC_CONTRACTS = 'tc_contracts';
    const TABLE_TC_COMMANDS = 'tc_commands';
    const TABLE_TC_TRAVELS = 'tc_travels';
    const TABLE_TC_VEHICLE = 'tc_vehicle';
    const TABLE_PA_GROUP = 'pa_group';
    const TABLE_PA_GROUP_STATE = 'pa_group_state';
    const TABLE_PA_PAYMENT = 'pa_payment';
    const TABLE_PA_PAYMENT_STATE = 'pa_payment_state';
    const TABLE_PA_BANK = 'pa_bank';
    const TABLE_PA_SMTP = 'pa_smtp';
    const TABLE_PA_GROUP_SMTP = 'pa_group_smtp';

    /**
     *
     * @var \DibiConnection
     */
    protected $connection;

    public function __construct($connection = NULL) {
        $this->connection = $connection;
    }

    /**
     * vyhleda akci|jednotku a pokud tam není, tak založí její záznam
     * @param type $skautisEventId
     * @param type $type
     * @return localId
     */
    public function getLocalId($skautisEventId, $type) {
        if (!($ret = $this->connection->fetchSingle("SELECT id FROM [" . self::TABLE_OBJECT . "] WHERE skautisId=%i AND type=%s LIMIT 1", $skautisEventId, $type))) {
            $ret = $this->connection->insert(self::TABLE_OBJECT, array("skautisId" => $skautisEventId, "type" => $type))->execute(\dibi::IDENTIFIER);
        }
        return $ret;
    }

    public function getSkautisId($localId) {
        return $this->connection->fetchSingle("SELECT skautisId FROM [" . self::TABLE_OBJECT . "] WHERE id=%i LIMIT 1", $localId);
    }

    public function getByEventId($skautisEventId, $type) {
        $ret = $this->connection->fetch("SELECT id as localId, prefix FROM  [" . self::TABLE_OBJECT . "] WHERE skautisId=%i AND type=%s LIMIT 1", $skautisEventId, $type);
        if (!$ret) {
            $this->getLocalId($skautisEventId, $type);
            $ret = $this->{__FUNCTION__}($skautisEventId, $type);
        }
        return $ret;
    }

}
