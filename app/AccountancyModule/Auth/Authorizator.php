<?php

namespace App\AccountancyModule\Auth;

use Model\UserService;

final class Authorizator implements IAuthorizator
{

    /** @var UserService */
    private $userService;

    /** @var array[] */
    private $resources;

    private const EVENT = "EV_EventGeneral";

    private const AVAILABLE_RESOURCES = [
        Event::class,
        Unit::class,
    ];

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function isAllowed(array $action, int $resourceId): bool
    {
        if(count($action) !== 2 || !in_array($action[0], self::AVAILABLE_RESOURCES)) {
            throw new \InvalidArgumentException("Unknown action");
        }
        return isset($this->getResource($action[0], $resourceId)[$action[1]]);
    }


    private function getResource(string $resource, int $id): array
    {
        if(!isset($this->resources[$resource][$id])) {
            $this->resources[$resource][$id] = $this->loadResource($resource, $id);
        }
        return $this->resources[$resource][$id];
    }

    private function loadResource(string $resource, int $id): array
    {
        try {
            if ($resource === Event::class) {
                return $this->userService->actionVerify(self::EVENT, $id);
            }
            throw new \InvalidArgumentException("Unknown resource $resource");
        } catch (\Skautis\Wsdl\PermissionException $exc) {
            return [];
        }
    }

}
