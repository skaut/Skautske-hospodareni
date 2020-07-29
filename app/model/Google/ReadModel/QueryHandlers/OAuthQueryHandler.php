<?php

declare(strict_types=1);

namespace Model\Google\ReadModel\QueryHandlers;

use Model\DTO\Google\OAuth as OAuthDTO;
use Model\DTO\Google\OAuthFactory;
use Model\Google\ReadModel\Queries\OAuthQuery;
use Model\Mail\Repositories\IGoogleRepository;

final class OAuthQueryHandler
{
    /** @var IGoogleRepository */
    private $repository;

    public function __construct(IGoogleRepository $repository)
    {
        $this->repository = $repository;
    }

    public function __invoke(OAuthQuery $query) : ?OAuthDTO
    {
        $oAuth = $this->repository->find($query->getOAuthId());
        if ($oAuth === null) {
            return null;
        }

        return OAuthFactory::create($oAuth);
    }
}
