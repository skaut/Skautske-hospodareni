<?php

declare(strict_types=1);

namespace Model\Google;

use Google_Service_Gmail;
use Google_Service_Gmail_Message;
use Model\Mail\Repositories\IGoogleRepository;
use Nette\Mail\IMailer;
use Nette\Mail\Message;
use function array_key_exists;
use function base64_encode;
use function sprintf;
use function str_replace;

class OAuthMailer implements IMailer
{
    private Google_Service_Gmail $gmailService;

    public function __construct(IGoogleRepository $googleRepository, OAuth $oAuth)
    {
        $client = $googleRepository->getClient();
        $token  = $client->fetchAccessTokenWithRefreshToken($oAuth->getToken());
        if (array_key_exists('error', $token)) {
            throw new InvalidOAuth(sprintf('%s => %s', $token['error'], $token['error_description']));
        }
        $client->setAccessToken($token);
        $this->gmailService = new Google_Service_Gmail($client);
    }

    public function send(Message $mail) : void
    {
        $gmsg = new Google_Service_Gmail_Message();
        $gmsg->setRaw(str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($mail->generateMessage())));
        $this->gmailService->users_messages->send('me', $gmsg);
    }
}
