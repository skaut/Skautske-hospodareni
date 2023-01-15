<?php

declare(strict_types=1);

namespace Model\Google\Handlers;

use Google\Service\Oauth2;
use Model\Google\Commands\SaveOAuth;
use Model\Google\Exception\OAuthNotFound;
use Model\Google\GoogleService;
use Model\Google\OAuth;
use Model\Mail\Repositories\IGoogleRepository;

final class SaveOAuthHandler
{
    public function __construct(private IGoogleRepository $repository, private GoogleService $googleService)
    {
    }

    public function __invoke(SaveOAuth $command): void
    {
        $client = $this->googleService->getClient();
        $token  = $client->fetchAccessTokenWithAuthCode($command->getCode());
        $client->setAccessToken($token);
        $email = (new Oauth2($client))->userinfo->get()->getEmail();

        try {
            $oAuth = $this->repository->findByUnitAndEmail($command->getUnitId(), $email);
            $oAuth->setToken($token['refresh_token']);
        } catch (OAuthNotFound) {
            $oAuth = OAuth::create($command->getUnitId(), $token['refresh_token'], $email);
        }

        $this->repository->save($oAuth);
    }
}
