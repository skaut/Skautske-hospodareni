<?php

declare(strict_types=1);

namespace Model\DTO\Google;

class OAuthFactory
{
    public static function create(\Model\Google\OAuth $origin) : OAuth
    {
        return new OAuth(
            $origin->getId(),
            $origin->getEmail(),
            $origin->getUnitId(),
            $origin->getUpdatedAt()
        );
    }
}
