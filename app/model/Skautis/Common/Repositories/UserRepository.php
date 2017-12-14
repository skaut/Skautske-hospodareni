<?php

declare(strict_types=1);

namespace Model\Skautis\Common\Repositories;

use Model\Common\Repositories\IUserRepository;
use Model\Common\User;
use Model\Common\UserNotFoundException;
use Skautis\Skautis;
use Skautis\Wsdl\PermissionException;

class UserRepository implements IUserRepository
{

    /** @var Skautis */
    private $skautis;

    public function __construct(Skautis $skautis)
    {
        $this->skautis = $skautis;
    }

    public function find(int $id): User
    {
        try {
            $user = $this->skautis->user->UserDetail(["ID" => $id]);
            if ($user instanceof \stdClass) {
                $person = $this->skautis->org->PersonDetail(['ID' => $user->ID_Person]);

                return new User($id, $user->Person, $person->Email);
            }
        } catch (PermissionException $e) {

        }
        throw new UserNotFoundException();
    }

}
