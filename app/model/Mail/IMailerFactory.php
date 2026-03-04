<?php

declare(strict_types=1);

namespace Model\Mail;

use Entity\GoogleOAuth;
use Nette\Mail\Mailer;

interface IMailerFactory
{
    public function create(GoogleOAuth $oAuth): Mailer;
}
