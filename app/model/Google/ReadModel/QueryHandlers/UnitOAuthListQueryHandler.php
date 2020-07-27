<?php

declare(strict_types=1);

namespace Model\Google\ReadModel\QueryHandlers;

use Model\DTO\Google\OAuth as OAuthDTO;
use Model\Google\OAuth;
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
        return array_map(function (OAuth $OAuth) : OAuthDTO {
            return new OAuthDTO(
                $OAuth->getId(),
                $OAuth->getEmail(),
                $OAuth->getUnitId(),
                $OAuth->getUpdatedAt()
            );
        }, $this->repository->findByUnitId($query->getUnitId()));
    }
}
