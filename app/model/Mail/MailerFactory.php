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

    public function __construct(IMailer $debugMailer, bool $enabled, MailTable $smtps)
    {
        $this->debugMailer = $debugMailer;
        $this->enabled = $enabled;
        $this->smtps = $smtps;
    }

    public function create(array $credentials): IMailer
    {
        return $this->enabled
            ? new SmtpMailer($credentials)
            : $this->debugMailer;
    }

}
