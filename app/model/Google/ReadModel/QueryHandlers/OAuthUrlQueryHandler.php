<?php

declare(strict_types=1);

namespace Model\Google\ReadModel\QueryHandlers;

use Model\Google\GoogleService;
use Model\Google\ReadModel\Queries\OAuthUrlQuery;

final class OAuthUrlQueryHandler
{
    private GoogleService $service;

    public function __construct(GoogleService $service)
    {
        $this->service = $service;
    }

    public function __invoke(OAuthUrlQuery $query) : string
    {
        return $this->service->getClient()->createAuthUrl();
    }
}
