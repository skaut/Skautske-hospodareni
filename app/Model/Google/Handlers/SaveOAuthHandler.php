<?php

declare(strict_types=1);

namespace App\Model\Google\Handlers;

use App\Model\Common\Services\EventBus;
use App\Model\Google\Commands\SaveOAuth;
use App\Model\Google\Entity\GoogleOAuth;
use App\Model\Google\Exception\OAuthNotFound;
use App\Model\Google\GoogleService;
use App\Model\Mail\Repositories\IGoogleRepository;
use App\Model\Payment\DomainEvents\OAuthWasRestored;
use Google\Service\Oauth2;

final class SaveOAuthHandler
{
    public function __construct(private IGoogleRepository $repository, private GoogleService $googleService, private EventBus $eventBus)
    {
    }

    public function __invoke(SaveOAuth $command): void
    {
        $client = $this->googleService->getClient();
        $token = $client->fetchAccessTokenWithAuthCode($command->getCode());
        $client->setAccessToken($token);
        $email = (new Oauth2($client))->userinfo->get()->getEmail();

        $wasInvalid = false;

        try {
            $oAuth = $this->repository->findByUnitAndEmail($command->getUnitId(), $email);
            $wasInvalid = $oAuth->isInvalid();
            $oAuth->setToken($token['refresh_token']);
        } catch (OAuthNotFound) {
            $oAuth = GoogleOAuth::create($command->getUnitId(), $token['refresh_token'], $email);
        }

        $this->repository->save($oAuth);

        if (! $wasInvalid) {
            return;
        }

        $this->eventBus->handle(new OAuthWasRestored($oAuth->getId()));
    }
}
