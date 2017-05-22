<?php

namespace App;

use Model\UserService;

class Authorizator implements IAuthorizator
{

    /** @var UserService */
    private $userService;

    /** @var array[] */
    private $resources;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function isAllowed(string $resource, int $id, string $action): bool
    {
        if(!in_array($resource, self::AVAILABLE_RESOURCES)) {
            throw new \InvalidArgumentException(
                "Unknown resource type $resource. Allowed types: ", implode(", ", self::AVAILABLE_RESOURCES)
            );
        }
        return isset($this->getResource($resource, $id)[$action]);
    }


    private function getResource(string $resource, int $id): array
    {
        if(!isset($this->resources[$resource][$id])) {
            try {
                $this->resources[$resource][$id] = $this->userService->actionVerify(self::EVENT_RESOURCE, $id);
            } catch (\Skautis\Wsdl\PermissionException $exc) {
                $this->resources[$resource][$id] = [];
            }
        }
        return $this->resources[$resource][$id];
    }

}
