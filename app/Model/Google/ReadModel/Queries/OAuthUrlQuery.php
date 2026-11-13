<?php

declare(strict_types=1);

namespace App\Model\Google\ReadModel\Queries;

use App\Model\Google\ReadModel\QueryHandlers\OAuthUrlQueryHandler;

/** @see OAuthUrlQueryHandler */
final class OAuthUrlQuery
{
    /**
     * @param string      $state     random value Google returns to the callback, it ties the callback to this user's session
     * @param string|null $loginHint e-mail of the account Google offers first, e.g. when connecting a refused account again
     */
    public function __construct(private string $state, private ?string $loginHint = null)
    {
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getLoginHint(): ?string
    {
        return $this->loginHint;
    }
}
