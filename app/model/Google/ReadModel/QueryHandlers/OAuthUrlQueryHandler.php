<?php

declare(strict_types=1);

namespace Model\Google\ReadModel\QueryHandlers;

use Model\Google\ReadModel\Queries\OAuthUrlQuery;
use Model\Mail\Repositories\IGoogleRepository;

final class OAuthUrlQueryHandler
{
    /** @var IGoogleRepository */
    private $repository;

    public function __construct(IGoogleRepository $repository)
    {
        $this->repository = $repository;
    }

    public function __invoke(OAuthUrlQuery $query) : string
    {
        return $this->repository->getAuthUrl();
    }
}
