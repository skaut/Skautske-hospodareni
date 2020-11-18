<?php

declare(strict_types=1);

namespace Model\Mail;

use Model\Google\GoogleService;
use Model\Google\OAuth;
use Model\Google\OAuthMailer;
use Nette\Mail\IMailer;

class MailerFactory implements IMailerFactory
{
    private IMailer $debugMailer;

    private bool $enabled;

    private GoogleService $googleService;

    public function __construct(IMailer $debugMailer, bool $enabled, GoogleService $googleService)
    {
        $this->debugMailer   = $debugMailer;
        $this->enabled       = $enabled;
        $this->googleService = $googleService;
    }

    public function create(OAuth $oAuth) : IMailer
    {
        if (! $this->enabled) {
            return $this->debugMailer;
        }

        return new OAuthMailer($this->googleService, $oAuth);
    }
}
