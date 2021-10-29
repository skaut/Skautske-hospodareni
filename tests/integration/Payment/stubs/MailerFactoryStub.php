<?php

declare(strict_types=1);

namespace Model\Payment;

use Model\Google\OAuth;
use Model\Mail\IMailerFactory;
use Nette\Mail\Mailer;

class MailerFactoryStub implements IMailerFactory
{
    private Mailer $mailer;

    public function create(OAuth $oAuth): Mailer
    {
        return $this->mailer;
    }

    public function setMailer(Mailer $mailer): void
    {
        $this->mailer = $mailer;
    }
}
