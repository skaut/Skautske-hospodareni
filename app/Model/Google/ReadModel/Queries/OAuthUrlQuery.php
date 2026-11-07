<?php

declare(strict_types=1);

namespace App\Model\Google\ReadModel\Queries;

use App\Model\Google\ReadModel\QueryHandlers\OAuthUrlQueryHandler;

/** @see OAuthUrlQueryHandler */
final class OAuthUrlQuery
{
    /** @param string $state random value Google returns to the callback, it ties the callback to this user's session */
    public function __construct(private string $state)
    {
    }

    public function getState(): string
    {
        return $this->state;
    }
}
