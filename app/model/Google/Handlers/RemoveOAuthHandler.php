<?php

declare(strict_types=1);

namespace Model\Google\Handlers;

use eGen\MessageBus\Bus\EventBus;
use Model\Google\Commands\RemoveOAuth;
use Model\Google\Exception\OAuthNotFound;
use Model\Mail\Repositories\IGoogleRepository;
use Model\Payment\DomainEvents\OAuthWasRemoved;

final class RemoveOAuthHandler
{
    /** @var IGoogleRepository */
    private $repository;

    /** @var EventBus */
    private $eventBus;

    public function __construct(IGoogleRepository $repository, EventBus $eventBus)
    {
        $this->repository = $repository;
        $this->eventBus   = $eventBus;
    }

    public function __invoke(RemoveOAuth $command) : void
    {
        try {
            $oAuth = $this->repository->find($command->getOAuthId());
        } catch (OAuthNotFound $exc) {
            return;
        }
        $this->eventBus->handle(new OAuthWasRemoved($command->getOAuthId()));

        $this->repository->remove($oAuth);
    }
}
