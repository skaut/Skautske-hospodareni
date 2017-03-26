<?php

namespace Model\Payment\Repositories;


use Model\Payment\User;
use Model\Payment\UserNotFoundException;
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

    public function find(int $id) : User
    {
        try {
            $user = $this->skautis->user->UserDetail(["ID" => $id]);
            if($user instanceof \stdClass) {
                $person = $this->skautis->org->PersonDetail(['ID' => $user->ID_Person]);
                return new User($id, $user->Person, $person->Email);
            }
        } catch(PermissionException $e) {

        }
        throw new UserNotFoundException();
    }

}
