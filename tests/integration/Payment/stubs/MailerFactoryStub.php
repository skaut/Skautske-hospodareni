<?php

declare(strict_types=1);

namespace App\Model\Payment;

use App\Model\Google\Entity\GoogleOAuth;
use App\Model\Mail\IMailerFactory;
use Nette\Mail\Mailer;
use Throwable;

class MailerFactoryStub implements IMailerFactory
{
    private Mailer $mailer;

    private ?Throwable $failure = null;

    public function create(GoogleOAuth $oAuth): Mailer
    {
        if ($this->failure !== null) {
            throw $this->failure;
        }

        return $this->mailer;
    }

    public function setMailer(Mailer $mailer): void
    {
        $this->mailer = $mailer;
    }

    /** Simulates e.g. a Google account whose token was revoked, the mailer then cannot be created. */
    public function failWith(?Throwable $failure): void
    {
        $this->failure = $failure;
    }
}
