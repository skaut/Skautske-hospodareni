<?php

declare(strict_types=1);

namespace App\Model\Payment;

use App\Model\Google\Entity\GoogleOAuth;
use App\Model\Mail\IMailerFactory;
use Nette\Mail\Mailer;

class MailerFactoryStub implements IMailerFactory
{
    private Mailer $mailer;

    public function create(GoogleOAuth $oAuth): Mailer
    {
        return $this->mailer;
    }

    public function setMailer(Mailer $mailer): void
    {
        $this->mailer = $mailer;
    }
}
