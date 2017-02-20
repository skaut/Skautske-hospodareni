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

    /**
     * MailerFactory constructor.
     * @param IMailer $debugMailer
     * @param bool $enabled
     */
    public function __construct(IMailer $debugMailer, bool $enabled)
    {
        $this->debugMailer = $debugMailer;
        $this->enabled = $enabled;
    }

    public function create(string $host, string $username, string $password, string $secure): IMailer
    {
        if (!$this->enabled) {
            return $this->debugMailer;
        }
        return new SmtpMailer([
            'host' => $host,
            'username' => $username,
            'password' => $password,
            'secure' => $secure,
        ]);
    }

}
