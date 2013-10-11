<?php

/**
 * používat pouze pro data ze skautISu, nikdy nenechávat aby uživatel zadal sám svoje ID!
 * @author sinacek
 */
class SkautISAuthenticator extends Nette\Object implements Nette\Security\IAuthenticator {

    public function authenticate(array $credentials) {
        $data = $credentials[0];
        return new Nette\Security\Identity($data->ID_User);
    }

}
