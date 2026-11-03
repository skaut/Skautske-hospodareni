<?php

declare(strict_types=1);

namespace App\Model\Mail;

use App\Model\Google\Entity\GoogleOAuth;
use App\Model\Google\GoogleService;
use App\Model\Google\OAuthMailer;
use Nette\Mail\Mailer;

class MailerFactory implements IMailerFactory
{
    /**
     * Each OAuth mailer refreshes the access token on creation, so one mailer serves all e-mails of a request.
     *
     * @var array<string, OAuthMailer>
     */
    private array $mailers = [];

    public function __construct(private Mailer $debugMailer, private bool $enabled, private GoogleService $googleService)
    {
    }

    public function create(GoogleOAuth $oAuth): Mailer
    {
        if (! $this->enabled) {
            return $this->debugMailer;
        }

        return $this->mailers[$oAuth->getId()->toString()] ??= new OAuthMailer($this->googleService, $oAuth);
    }
}
