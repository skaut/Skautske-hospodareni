<?php

declare(strict_types=1);

namespace App\Model\Mail;

use App\Model\Google\Entity\GoogleOAuth;
use Nette\Mail\Mailer;

interface IMailerFactory
{
    public function create(GoogleOAuth $oAuth): Mailer;
}
