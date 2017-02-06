<?php

namespace Model\Mail;

use Nette\Mail\IMailer;

interface IMailerFactory
{

    public function create(string $host, string $username, string $password, string $secure) : IMailer;

}
