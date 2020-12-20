<?php

declare(strict_types=1);

namespace Model\Payment;

use Model\Google\OAuth;
use Model\Mail\IMailerFactory;
use Nette\Mail\IMailer;

class MailerFactoryStub implements IMailerFactory
{
    private IMailer $mailer;

    public function create(OAuth $oAuth): IMailer
    {
        return $this->mailer;
    }

    public function setMailer(IMailer $mailer): void
    {
        $this->mailer = $mailer;
    }
}
