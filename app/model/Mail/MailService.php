<?php

namespace Model;

use Nette\Mail\Message,
    \Nette\Mail\SendmailMailer;

/**
 * @author Hána František
 */
class MailService extends BaseService {

    protected $sendEmail;

    const EMAIL_SENDER = "platby@skauting.cz";

    public function __construct($skautIS = NULL, $connection = NULL, $sendEmail = FALSE) {
        parent::__construct($skautIS, $connection);
        $this->sendEmail = $sendEmail;
    }

    private function send(Message $mail, $unitId = NULL) {
        if ($this->sendEmail) {
            if ($unitId !== NULL && ($smtpMailer = $this->getSmtpMailer($unitId))) {
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

    public function sendPaymentInfo(\Nette\Application\UI\ITemplate $template, $to, $subject, $body, $unitId = NULL) {
        $template->setFile(dirname(__FILE__) . "/mail.base.latte");
        $template->body = $body;
        $mail = new Message;
        $mail->setFrom(self::EMAIL_SENDER)
                ->addTo($to)
                ->setSubject($subject)
                ->setHtmlBody($template);
        return $this->send($mail, $unitId);
    }

    protected function getSmtpMailer($unitId) {
        if (!is_array($unitId)) {
            $unitId = array($unitId);
        }

        $data = $this->table->getSmtp($unitId);
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

}
