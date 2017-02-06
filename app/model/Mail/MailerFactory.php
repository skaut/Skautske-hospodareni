<?php

namespace Model\Mail;

use Nette\Mail\IMailer;
use Nette\Mail\SmtpMailer;

class MailerFactory implements IMailerFactory
{

    /** @var IMailer|NULL $debugMailer */
    private $debugMailer;

    /** @var bool */
    private $debug;

    /**
     * MailerFactory constructor.
     * @param IMailer $debugMailer
     * @param bool $debug
     */
    public function __construct(IMailer $debugMailer, bool $debug)
    {
        $this->debugMailer = $debugMailer;
        $this->debug = $debug;
    }

    public function create(string $host, string $username, string $password, string $secure): IMailer
    {
        if($this->debug) {
            return $this->debugMailer;
        }
        return new SmtpMailer(array(
            'host' => $host,
            'username' => $username,
            'password' => $password,
            'secure' => $secure,
        ));
    }


}
