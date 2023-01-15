<?php

declare(strict_types=1);

namespace Model\Mail;

use Model\Google\GoogleService;
use Model\Google\OAuth;
use Model\Google\OAuthMailer;
use Nette\Mail\Mailer;

class MailerFactory implements IMailerFactory
{
    public function __construct(private Mailer $debugMailer, private bool $enabled, private GoogleService $googleService)
    {
    }

    public function create(OAuth $oAuth): Mailer
    {
        if (! $this->enabled) {
            return $this->debugMailer;
        }

        return new OAuthMailer($this->googleService, $oAuth);
    }
}
