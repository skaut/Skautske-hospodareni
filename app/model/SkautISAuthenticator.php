<?php
/**
 * používat pouze pro data ze skautISu, nikdy nenechávat aby uživatel zadal sám svoje ID!
 * @author Hána František
 */
class SkautISAuthenticator extends Object implements IAuthenticator {

    public function authenticate(array $credentials) {
        $data = $credentials[0];
        return new Identity($data->ID_User);
    }

}
