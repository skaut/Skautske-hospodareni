<?php

namespace Model;

use Dibi\Connection;
use Nette\Mail\Message,
    \Nette\Mail\SendmailMailer;

/**
 * @author Hána František
 * @property MailTable $table
 */
class MailService extends BaseService {

    protected $sendEmail;

    const EMAIL_SENDER = "platby@skauting.cz";

    public function __construct(Connection $connection, $sendEmail = FALSE)
    {
        parent::__construct(NULL, $connection);
        $this->sendEmail = $sendEmail;
    }

    protected function getSmtpMailer($groupId) {
        $data = $this->getSmtpByGroup($groupId);
        if ($data != NULL) {
            return new \Nette\Mail\SmtpMailer(array(
                'host' => $data['host'],
                'username' => $data['username'],
                'password' => $data['password'],
                'secure' => $data['secure'],
            ));
        }
        return FALSE;
    }

    private function send(Message $mail, $groupId = NULL) {
        if ($this->sendEmail) {
            if ($groupId !== NULL && ($smtpMailer = $this->getSmtpMailer($groupId))) {
                $mailer = $smtpMailer;
            } else {
                $mailer = new SendmailMailer();
            }
            $mailer->send($mail);
            return TRUE;
        } else {
            echo $mail->getHtmlBody() . "<hr>";
            die();
        }
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
        return $this->send($mail, $groupId);
    }

}
