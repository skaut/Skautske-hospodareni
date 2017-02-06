<?php

namespace Model;

use Dibi\Connection;
use Model\Mail\IMailerFactory;
use Nette\Mail\IMailer;
use Nette\Mail\Message;

/**
 * @author Hána František
 * @property MailTable $table
 */
class MailService extends BaseService {

    /** @var IMailerFactory */
    private $mailerFactory;

    /** @var IMailer */
    private $defaultMailer;

    const EMAIL_SENDER = "platby@skauting.cz";

    public function __construct(Connection $connection, IMailerFactory $mailerFactory, IMailer $defaultMailer)
    {
        parent::__construct(NULL, $connection);
        $this->mailerFactory = $mailerFactory;
        $this->defaultMailer = $defaultMailer;
    }

    private function getMailer($groupId) : IMailer
    {
        if($groupId && $data = $this->getSmtpByGroup($groupId)) {
            return $this->mailerFactory->create(
                $data['host'],
                $data['username'],
                $data['password'],
                $data['secure']
            );
        }
        return $this->defaultMailer;
    }

    //SMTP

    public function get($id) {
        return $this->table->get($id);
    }

    public function getAll($unitId) {
        return $this->table->getAll($unitId);
    }

    public function getPairs($unitId) {
        return $this->table->getPairs($unitId);
    }

    public function getSmtpByGroup($groupId) {
        return $this->table->getSmtpByGroup($groupId);
    }

    public function addSmtp($unitId, $host, $username, $password, $secure = "ssl") {
        return $this->table->addSmtp($unitId, $host, $username, $password, $secure);
    }

    public function removeSmtp($unitId, $id) {
        return $this->table->removeSmtp($unitId, $id);
    }

    public function updateSmtp($unitId, $id, $data) {
        return $this->table->updateSmtp($unitId, $id, $data);
    }

    //SMTP GROUP

    public function addSmtpGroup($groupId, $smtpId) {
        return $this->table->addSmtpGroup($groupId, $smtpId);
    }

    public function removeSmtpGroup($groupId) {
        return $this->table->removeSmtpGroup($groupId);
    }

    //Odesílání emailů

    public function sendPaymentInfo(\Nette\Application\UI\ITemplate $template, $to, $subject, $body, $groupId = NULL, $qrPrefix = NULL) {
        $template->setFile(dirname(__FILE__) . "/mail.base.latte");
        $template->body = $body;
        $mail = new Message;
        $mail->setFrom(self::EMAIL_SENDER)
                ->addTo($to)
                ->setSubject($subject)
                ->setHtmlBody($template, $qrPrefix);
        $this->getMailer($groupId)->send($mail);
        return TRUE;
    }

}
