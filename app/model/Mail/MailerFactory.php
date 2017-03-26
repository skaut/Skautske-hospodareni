<?php

namespace Model\Mail;

use Nette\Mail\IMailer;
use Nette\Mail\SmtpMailer;

class MailerFactory implements IMailerFactory
{

    /** @var IMailer|NULL $debugMailer */
    private $debugMailer;

    /** @var bool */
    private $enabled;

    public function __construct(IMailer $debugMailer, bool $enabled)
    {
        $this->debugMailer = $debugMailer;
        $this->enabled = $enabled;
    }

    public function create(array $credentials): IMailer
    {
        return $this->enabled
            ? new SmtpMailer($credentials)
            : $this->debugMailer;
    }

}
