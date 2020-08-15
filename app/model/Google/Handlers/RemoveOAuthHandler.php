<?php

declare(strict_types=1);

namespace Model\Google\Handlers;

use Model\Google\Commands\RemoveOAuth;
use Model\Google\Exception\OAuthNotFound;
use Model\Mail\Repositories\IGoogleRepository;

final class RemoveOAuthHandler
{
    /** @var IGoogleRepository */
    private $repository;

    public function __construct(IGoogleRepository $repository)
    {
        $this->repository = $repository;
    }

    public function __invoke(RemoveOAuth $command) : void
    {
        try {
            $oAuth = $this->repository->find($command->getOAuthId());
        } catch (OAuthNotFound $exc) {
            return;
        }

        $this->repository->remove($oAuth);
    }
}
