<?php
/**
 * používat pouze pro data ze skautISu, nikdy nenechávat aby uživatel zadal sám svoje ID
 * @author sinacek
 */
class SkautISAuthenticator extends Object implements IAuthenticator {
//    const USERS_TABLE = 'acl_users';
//    const ROLES_TABLE = 'acl_roles';

    public function authenticate(array $credentials) {
//        $skautID = $credentials[self::USERNAME];
//        $row = dibi::fetch('SELECT  u.*,
//                                    ro.label as role
//                                    FROM [' . self::USERS_TABLE . '] as u
//                                        RIGHT JOIN [' . self::ROLES_TABLE . '] as ro ON (u.role = ro.id)
//                                    WHERE skautID=%s', $skautID);
//        if (!$row) {
//            throw new AuthenticationException("Uživatel ze skautISu jeste zde nemá registraci.");
//        }
//        return new Identity($row->username, $row->role, $row);
        $data = $credentials[0];
        $skautis = SkautIS::getInstance();
        $person = $skautis->org->personDetail(array("ID"=>$data->ID_Person));
        $data->DisplayName = $person->DisplayName;
        return new Identity($data->ID, NULL, $data);
    }

}
