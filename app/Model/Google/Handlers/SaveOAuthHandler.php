<?php

declare(strict_types=1);

namespace App\Model\Google\Handlers;

use App\Model\Google\Commands\SaveOAuth;
use App\Model\Google\Entity\GoogleOAuth;
use App\Model\Google\Exception\OAuthNotFound;
use App\Model\Google\GoogleService;
use App\Model\Mail\Repositories\IGoogleRepository;
use Google\Service\Oauth2;

final class SaveOAuthHandler
{
    public function __construct(private IGoogleRepository $repository, private GoogleService $googleService)
    {
    }

    public function __invoke(SaveOAuth $command): void
    {
        $client = $this->googleService->getClient();
        $token = $client->fetchAccessTokenWithAuthCode($command->getCode());
        $client->setAccessToken($token);
        $email = (new Oauth2($client))->userinfo->get()->getEmail();

        try {
            $oAuth = $this->repository->findByUnitAndEmail($command->getUnitId(), $email);
            $oAuth->setToken($token['refresh_token']);
        } catch (OAuthNotFound) {
            $oAuth = GoogleOAuth::create($command->getUnitId(), $token['refresh_token'], $email);
        }

        $this->repository->save($oAuth);
    }
}
