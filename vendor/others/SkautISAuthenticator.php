<?php
namespace Sinacek;

use Nette;

/**
 * používat pouze pro data ze skautISu, nikdy nenechávat aby uživatel zadal sám svoje ID!
 * @author Hána František <sinacek@gmail.com>
 */
final class SkautisAuthenticator implements Nette\Security\IAuthenticator
{

    public function authenticate(array $credentials) {
        $data = $credentials[0];
        return new Nette\Security\Identity($data->ID_User);
    }

}
