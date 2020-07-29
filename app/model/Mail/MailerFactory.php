<?php

declare(strict_types=1);

namespace Model\Mail;

use Model\Google\OAuth;
use Model\Google\OAuthMailer;
use Model\Mail\Repositories\IGoogleRepository;
use Nette\Mail\IMailer;

class MailerFactory implements IMailerFactory
{
    /** @var IMailer */
    private $debugMailer;

    /** @var bool */
    private $enabled;

    /** @var IGoogleRepository */
    private $googleRepository;

    public function __construct(IMailer $debugMailer, bool $enabled, IGoogleRepository $googleRepository)
    {
        $this->debugMailer      = $debugMailer;
        $this->enabled          = $enabled;
        $this->googleRepository = $googleRepository;
    }

    public function create(OAuth $oAuth) : IMailer
    {
        if (! $this->enabled) {
            return $this->debugMailer;
        }

        return new OAuthMailer($this->googleRepository->getGmailService($oAuth));
    }
}
