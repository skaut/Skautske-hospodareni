<?php

namespace Model;

use Model\DTO\Payment\MailFactory;

/**
 * @author Hána František
 */
class MailService
{

    /** @var MailTable */
    private $table;

    /** @var UnitService */
    private $units;

    public function __construct(MailTable $table, UnitService $units)
    {
        $this->table = $table;
        $this->units = $units;
    }

    public function get($id)
    {
        $row = $this->table->get($id);
        return $row !== FALSE ? MailFactory::create($row) : NULL;
    }

    public function getAll(int $unitId) : array
    {
        $mails = $this->table->getAll($this->getUnitIds($unitId));
        return array_map([MailFactory::class, 'create'], $mails);
    }

    public function getPairs(int $unitId) : array
    {
        return $this->table->getPairs($this->getUnitIds($unitId));
    }

    public function getSmtpByGroup($groupId)
    {
        return $this->table->getSmtpByGroup($groupId);
    }

    public function addSmtp($unitId, $host, $username, $password, $secure = "ssl")
    {
        return $this->table->addSmtp($unitId, $host, $username, $password, $secure);
    }

    public function removeSmtp($unitId, $id)
    {
        return $this->table->removeSmtp($unitId, $id);
    }

    public function updateSmtp($unitId, $id, $data)
    {
        return $this->table->updateSmtp($unitId, $id, $data);
    }

    /**
     * @param int $unitId
     * @return int[]
     */
    private function getUnitIds(int $unitId) : array
    {
        return [$unitId, $this->units->getOficialUnit($unitId)->ID];
    }

}
