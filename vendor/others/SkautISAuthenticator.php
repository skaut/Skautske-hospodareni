<?php

declare(strict_types=1);

namespace Sinacek;

use Nette;

/**
 * používat pouze pro data ze skautISu, nikdy nenechávat aby uživatel zadal sám svoje ID!
 */
final class SkautisAuthenticator implements Nette\Security\IAuthenticator
{
    public function authenticate(array $credentials)
    {
        return new Nette\Security\Identity($credentials[0]['user']->ID, $credentials[0]['roles']);
    }
}
