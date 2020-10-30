<?php

declare(strict_types=1);

namespace Model\Google;

use Google_Client;
use Google_Service_Gmail;

class GoogleService
{
    private string $tokenUri;
    private string $credentialsPath;

    public function __construct(string $tokenUri, string $credentialsPath)
    {
        $this->tokenUri        = $tokenUri;
        $this->credentialsPath = $credentialsPath;
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
