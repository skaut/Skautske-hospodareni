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
                return new User($id, $user->Person);
            }
        } catch(PermissionException $e) {

        }
        throw new UserNotFoundException();
    }

}
