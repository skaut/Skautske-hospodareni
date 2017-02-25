<?php

namespace Model;

use Model\Mail\IMailerFactory;
use Nette\Mail\IMailer;
use Nette\Mail\Message;

/**
 * @author Hána František
 */
class MailService
{

    /** @var MailTable */
    private $table;

    public function __construct(MailTable $table)
    {
        $this->table = $table;
    }

    public function get($id)
    {
        return $this->table->get($id);
    }

    public function getAll($unitId)
    {
        return $this->table->getAll($unitId);
    }

    public function getPairs($unitId)
    {
        return $this->table->getPairs($unitId);
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

}
