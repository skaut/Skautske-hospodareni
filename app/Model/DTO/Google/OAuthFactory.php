<?php

declare(strict_types=1);

namespace App\Model\DTO\Google;

class OAuthFactory
{
    public static function create(\App\Model\Google\Entity\GoogleOAuth $origin): OAuth
    {
        return new OAuth(
            $origin->getId(),
            $origin->getEmail(),
            $origin->getUnitId(),
            $origin->getUpdatedAt(),
        );
    }
}
