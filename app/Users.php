<?php

class Users extends Object implements IAuthenticator {
    const USERS_TABLE = 'users';
    const ROLES_TABLE = 'users_roles';

    public function authenticate(array $credentials) {
        $username = $credentials[self::USERNAME];
        $modelUser = new UserService();
        $password = $modelUser->makePassword($credentials[self::PASSWORD], $credentials[self::USERNAME]);

        $row = dibi::fetch('SELECT  u.*,
                                    ro.parentId as parentId,
                                    ro.label as role
                                    FROM [' . self::USERS_TABLE . '] as u
                                        RIGHT JOIN [' . self::ROLES_TABLE . '] as ro ON (u.role = ro.id)
                                    WHERE username=%s', $username);

        if (!$row || $row->password !== $password) {
            throw new AuthenticationException("Špatné uživatelské jméno nebo heslo");
        }

        $role[] = $row->role;

        return new Identity($row->username, $row->role, $row); // vrátíme identitu
    }

}
