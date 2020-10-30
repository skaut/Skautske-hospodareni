<?php

declare(strict_types=1);

namespace Model\Google;

use Google_Client;
use Google_Service_Gmail;

class GoogleService
{
    private string $credentialsPath;
    private string $tokenUri;

    public function __construct(string $credentialsPath, string $tokenUri)
    {
        $this->credentialsPath = $credentialsPath;
        $this->tokenUri        = $tokenUri;
    }

    public function getClient() : Google_Client
    {
        $client = new Google_Client();
        $client->setApplicationName('Skautské hospodaření online');
        $client->setScopes([
            Google_Service_Gmail::GMAIL_SEND,
        ]);
        $client->setAuthConfig($this->credentialsPath);
        $client->setRedirectUri($this->tokenUri);
        $client->setAccessType('offline');
        $client->setIncludeGrantedScopes(true);
        $client->setPrompt('select_account consent');

        return $client;
    }
}
