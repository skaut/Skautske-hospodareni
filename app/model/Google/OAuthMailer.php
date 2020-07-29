<?php

declare(strict_types=1);

namespace Model\Google;

use Google_Service_Gmail;
use Google_Service_Gmail_Message;
use Nette\Mail\IMailer;
use Nette\Mail\Message;
use function base64_encode;
use function str_replace;

class OAuthMailer implements IMailer
{
    private Google_Service_Gmail $gmailService;

    public function __construct(Google_Service_Gmail $gmailService)
    {
        $this->gmailService = $gmailService;
    }

    public function send(Message $mail) : void
    {
        $gmsg = new Google_Service_Gmail_Message();
        $gmsg->setRaw(str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($mail->generateMessage())));
        $this->gmailService->users_messages->send('me', $gmsg);
    }
}
