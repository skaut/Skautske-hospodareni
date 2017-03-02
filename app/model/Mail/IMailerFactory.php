<?php

namespace Model\Mail;

use Nette\Mail\IMailer;

interface IMailerFactory
{

    public function create(?int $smtpId): IMailer;

}
