<?php

declare(strict_types=1);

namespace Model\Google\Handlers;

use Model\Google\Commands\SaveOAuth;
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
        $this->repository->saveAuthCode($command->getCode(), $command->getUnitId());
    }
}
