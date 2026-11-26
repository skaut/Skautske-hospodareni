<?php

declare(strict_types=1);

namespace App\Model\Google;

use App\Model\Google\Entity\GoogleOAuth;
use Google\Service\Gmail;
use Nette\Mail\Mailer;
use Nette\Mail\Message;

use function array_key_exists;
use function base64_encode;
use function is_string;
use function str_replace;

class OAuthMailer implements Mailer
{
    private Gmail $gmailService;

    /** @throws InvalidOAuth when Google refuses the token of the account */
    public function __construct(GoogleService $googleService, GoogleOAuth $oAuth)
    {
        $client = $googleService->getClient();
        $token = $client->fetchAccessTokenWithRefreshToken($oAuth->getToken());
        if (array_key_exists('error', $token)) {
            $description = $token['error_description'] ?? null;

            throw new InvalidOAuth($oAuth->getEmail(), (string) $token['error'], is_string($description) ? $description : null);
        }

        $client->setAccessToken($token);
        $this->gmailService = new Gmail($client);
    }

    public function send(Message $mail): void
    {
        $gmsg = new Gmail\Message();
        $gmsg->setRaw($this->urlSafeBase64Encode($mail->generateMessage()));
        $this->gmailService->users_messages->send('me', $gmsg);
    }

    private function urlSafeBase64Encode(string $msg): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($msg));
    }
}
