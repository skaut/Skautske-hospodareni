<?php

declare(strict_types=1);

namespace Model\Google\Handlers;

use Google_Service_Oauth2;
use Model\Google\Commands\SaveOAuth;
use Model\Google\Exception\OAuthNotFound;
use Model\Google\OAuth;
use Model\Mail\Repositories\IGoogleRepository;

final class SaveOAuthHandler
{
    /** @var IGoogleRepository */
    private $repository;

    public function __construct(IGoogleRepository $repository)
    {
        $this->repository = $repository;
    }

    public function __invoke(SaveOAuth $command) : void
    {
        $client = $this->repository->getClient();
        $token  = $client->fetchAccessTokenWithAuthCode($command->getCode());
        $client->setAccessToken($token);
        $email = (new Google_Service_Oauth2($client))->userinfo->get()->getEmail();

        try {
            $oAuth = $this->repository->findByUnitAndEmail($command->getUnitId(), $email);
            $oAuth->setToken($token['refresh_token']);
        } catch (OAuthNotFound $exc) {
            $oAuth = OAuth::create($command->getUnitId(), $token['refresh_token'], $email);
        }
        $this->repository->save($oAuth);
    }
}
