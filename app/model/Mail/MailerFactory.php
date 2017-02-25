<?php

namespace Model\Mail;

use Model\MailTable;
use Nette\Mail\IMailer;
use Nette\Mail\SmtpMailer;

class MailerFactory implements IMailerFactory
{

    /** @var IMailer|NULL $debugMailer */
    private $debugMailer;

    /** @var MailTable */
    private $smtps;

    /** @var bool */
    private $enabled;

    /**
     * MailerFactory constructor.
     * @param IMailer $debugMailer
     * @param bool $enabled
     * @param MailTable $smtps
     */
    public function __construct(IMailer $debugMailer, bool $enabled, MailTable $smtps)
    {
        $this->debugMailer = $debugMailer;
        $this->enabled = $enabled;
        $this->smtps = $smtps;
    }

    public function create(?int $smtpId): IMailer
    {
        if($smtpId === NULL || !($smtp = $this->smtps->get($smtpId))) {
            throw new MailerNotFoundException();
        }

        if (!$this->enabled) {
            return $this->debugMailer;
        }

        return new SmtpMailer($smtp);
    }

}
