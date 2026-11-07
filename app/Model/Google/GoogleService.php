<?php

declare(strict_types=1);

namespace App\Model\Google;

use Google\Client;
use Google\Service\Gmail;
use Google\Service\Oauth2;
use GuzzleHttp\Client as HttpClient;

class GoogleService
{
    /**
     * A web request on the hosting may run 30 s and the task queue starts new e-mails until 20 s, so one call
     * to Google must end in time. A call cut off while sending leaves the e-mail uncertain, never sent twice.
     */
    private const TIMEOUT_SECONDS = 8;
    private const CONNECT_TIMEOUT_SECONDS = 4;

    public function __construct(private string $credentialsPath, private string $tokenUri)
    {
    }

    public function getClient(): Client
    {
        $client = new Client();
        $client->setApplicationName('Skautské hospodaření online');
        $client->setScopes([
            Gmail::GMAIL_SEND,
            Oauth2::USERINFO_EMAIL,
        ]);
        $client->setAuthConfig($this->credentialsPath);
        $client->setRedirectUri($this->tokenUri);
        $client->setAccessType('offline');
        $client->setIncludeGrantedScopes(true);
        $client->setPrompt('select_account consent');
        // Same options as Google\Client::createDefaultHttpClient(), plus the timeouts.
        $client->setHttpClient(new HttpClient([
            'base_uri' => $client->getConfig('base_path'),
            'http_errors' => false,
            'timeout' => self::TIMEOUT_SECONDS,
            'connect_timeout' => self::CONNECT_TIMEOUT_SECONDS,
        ]));

        return $client;
    }
}
