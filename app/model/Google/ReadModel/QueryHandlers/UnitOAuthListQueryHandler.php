<?php

declare(strict_types=1);

namespace Model\Google\ReadModel\QueryHandlers;

use Model\DTO\Google\OAuth as OAuthDTO;
use Model\DTO\Google\OAuthFactory;
use Model\Google\ReadModel\Queries\UnitOAuthListQuery;
use Model\Mail\Repositories\IGoogleRepository;
use function array_map;

final class UnitOAuthListQueryHandler
{
    /** @var IGoogleRepository */
    private $repository;

    public function __construct(IGoogleRepository $repository)
    {
        $this->repository = $repository;
    }

    /** @return OAuthDTO[] */
    public function __invoke(UnitOAuthListQuery $query) : array
    {
        return array_map([OAuthFactory::class, 'create'], $this->repository->findByUnit($query->getUnitId()));
    }
}
