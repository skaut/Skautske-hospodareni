<?php

declare(strict_types=1);

namespace Model\Google\ReadModel\QueryHandlers;

use Model\Google\GoogleService;
use Model\Google\ReadModel\Queries\OAuthUrlQuery;

final class OAuthUrlQueryHandler
{
    public function __construct(private GoogleService $service)
    {
    }

    public function __invoke(OAuthUrlQuery $query): string
    {
        return $this->service->getClient()->createAuthUrl();
    }
}
