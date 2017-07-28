<?php

namespace App\AccountancyModule\Auth;

use Model\UserService;

final class Authorizator implements IAuthorizator
{

    /** @var UserService */
    private $userService;

    /** @var array[] */
    private $resources;

    private const RESOURCE_NAMES = [
        Event::class => "EV_EventGeneral",
        Unit::class => "OU_Unit",
    ];

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

        return in_array($action[1], $this->getResource($action[0], $resourceId), TRUE);
    }


    /**
     * @param string $resource
     * @param int $id
     * @return string[]
     */
    private function getResource(string $resource, int $id): array
    {
        if(!isset($this->resources[$resource][$id])) {
            $this->resources[$resource][$id] = $this->loadResource($resource, $id);
        }
        return $this->resources[$resource][$id];
    }

    /**
     * @param string $resource
     * @param int $id
     * @return string[]
     */
    private function loadResource(string $resource, int $id): array
    {
        try {
            return $this->userService->getAvailableActions(self::RESOURCE_NAMES[$resource], $id);
        } catch (\Skautis\Wsdl\PermissionException $exc) {
            return [];
        }
    }

}
