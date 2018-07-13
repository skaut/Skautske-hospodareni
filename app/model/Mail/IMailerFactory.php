<?php

declare(strict_types=1);

namespace Model\Mail;

use Model\Payment\MailCredentials;
use Nette\Mail\IMailer;

interface IMailerFactory
{
    public function create(MailCredentials $credentials) : IMailer;
}
