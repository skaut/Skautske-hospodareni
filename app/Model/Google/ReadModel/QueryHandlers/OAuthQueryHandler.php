<?php

declare(strict_types=1);

namespace App\Model\Google\ReadModel\QueryHandlers;

use App\Model\DTO\Google\OAuth as OAuthDTO;
use App\Model\DTO\Google\OAuthFactory;
use App\Model\Google\Exception\OAuthNotFound;
use App\Model\Google\ReadModel\Queries\OAuthQuery;
use App\Model\Mail\Repositories\IGoogleRepository;

final class OAuthQueryHandler
{
    public function __construct(private IGoogleRepository $repository)
    {
    }

    public function __invoke(OAuthQuery $query): ?OAuthDTO
    {
        try {
            $oAuth = $this->repository->find($query->getOAuthId());
        } catch (OAuthNotFound) {
            return null;
        }

        return OAuthFactory::create($oAuth);
    }
}
