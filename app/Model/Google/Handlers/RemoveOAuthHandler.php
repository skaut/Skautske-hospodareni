<?php

declare(strict_types=1);

namespace App\Model\Google\Handlers;

use App\Model\Common\Services\EventBus;
use App\Model\Google\Commands\RemoveOAuth;
use App\Model\Google\Exception\OAuthNotFound;
use App\Model\Mail\Repositories\IGoogleRepository;
use App\Model\Payment\DomainEvents\OAuthWasRemoved;

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
