<?php

declare(strict_types=1);

namespace App\Model\Mail;

use App\Model\Google\Entity\GoogleOAuth;
use App\Model\Google\GoogleService;
use App\Model\Google\InvalidOAuth;
use App\Model\Google\OAuthMailer;
use App\Model\Google\OAuthValidity;
use DateTimeImmutable;
use Nette\Mail\Mailer;

class MailerFactory implements IMailerFactory
{
    /**
     * Each OAuth mailer refreshes the access token on creation, so one mailer serves all e-mails of a request.
     *
     * @var array<string, OAuthMailer>
     */
    private array $mailers = [];

    public function __construct(
        private Mailer $debugMailer,
        private bool $enabled,
        private GoogleService $googleService,
        private OAuthValidity $validity,
    ) {
    }

    /**
     * Remembers when Google refuses the account and when it accepts a refused account again.
     *
     * @throws InvalidOAuth
     */
    public function create(GoogleOAuth $oAuth): Mailer
    {
        if (! $this->enabled) {
            return $this->debugMailer;
        }

        $id = $oAuth->getId()->toString();

        if (isset($this->mailers[$id])) {
            return $this->mailers[$id];
        }

        try {
            $mailer = new OAuthMailer($this->googleService, $oAuth);
        } catch (InvalidOAuth $exception) {
            if ($exception->isRevoked()) {
                $this->validity->markInvalid($oAuth, $exception, new DateTimeImmutable());
            }

            throw $exception;
        }

        if ($oAuth->isInvalid()) {
            $this->validity->markValid($oAuth->getId());
        }

        return $this->mailers[$id] = $mailer;
    }
}
