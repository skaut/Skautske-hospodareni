<?php

declare(strict_types=1);

namespace Model\Mail;

use Model\Google\OAuth;
use Nette\Mail\Mailer;

interface IMailerFactory
{
    public function create(OAuth $oAuth): Mailer;
}
