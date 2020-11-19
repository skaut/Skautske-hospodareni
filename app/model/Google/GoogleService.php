<?php

declare(strict_types=1);

namespace Model\Google;

use Google\Client;
use Google_Service_Gmail;
use Google_Service_Oauth2;

class GoogleService
{
    private string $credentialsPath;
    private string $tokenUri;

    public function __construct(string $credentialsPath, string $tokenUri)
    {
        $this->credentialsPath = $credentialsPath;
        $this->tokenUri        = $tokenUri;
    }

    public function getClient() : Client
    {
        $client = new Client();
        $client->setApplicationName('Skautské hospodaření online');
        $client->setScopes([
            Google_Service_Gmail::GMAIL_SEND,
            Google_Service_Oauth2::USERINFO_EMAIL,
        ]);
        $client->setAuthConfig($this->credentialsPath);
        $client->setRedirectUri($this->tokenUri);
        $client->setAccessType('offline');
        $client->setIncludeGrantedScopes(true);
        $client->setPrompt('select_account consent');

        return $client;
    }
}
