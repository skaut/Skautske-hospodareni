<?php

declare(strict_types=1);

namespace Model\Google\Handlers;

use Model\Common\Services\EventBus;
use Model\Google\Commands\RemoveOAuth;
use Model\Google\Exception\OAuthNotFound;
use Model\Mail\Repositories\IGoogleRepository;
use Model\Payment\DomainEvents\OAuthWasRemoved;

final class RemoveOAuthHandler
{
    public function __construct(private IGoogleRepository $repository, private EventBus $eventBus)
    {
    }

    public function __invoke(RemoveOAuth $command): void
    {
        try {
            $oAuth = $this->repository->find($command->getOAuthId());
        } catch (OAuthNotFound) {
            return;
        }

        $this->eventBus->handle(new OAuthWasRemoved($command->getOAuthId()));

        $this->repository->remove($oAuth);
    }
}
