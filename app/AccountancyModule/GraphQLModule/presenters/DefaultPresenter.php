<?php

namespace App\AccountancyModule\GraphQLModule;

use App\AccountancyModule\BasePresenter;
use App\AccountancyModule\GraphQLModule\Schema\UserType;
use Youshido\GraphQL\Execution\Processor;
use Youshido\GraphQL\Schema\Schema;
use Youshido\GraphQL\Type\Object\ObjectType;

class DefaultPresenter extends BasePresenter
{

    public function renderDefault(): void
    {
        $rootQueryType = new ObjectType([
            'name' => 'Root',
            'fields' => [
                'user' => [
                    'type' => new UserType(),
                    'resolve' => function ($source, $args, $info) {
                        $roles = array_map(function (\stdClass $role) {
                            return [
                                'id' => $role->ID,
                                'name' => ($role->RegistrationNumber ? ($role->RegistrationNumber . ' - ') : '') . $role->Role,
                            ];
                        }, $this->userService->getAllSkautisRoles());

                        return [
                            'activeRoleId' => $this->userService->getRoleId(),
                            'roles' => $roles,
                        ];
                    }
                ]
            ]
        ]);

        $schema = new Schema([
            'query' => $rootQueryType,
        ]);


        $processor = new Processor($schema);

        $request = \json_decode($this->getHttpRequest()->getRawBody());

        $processor->processPayload($request->query, \json_encode($request->variables));
        $this->sendJson($processor->getResponseData());
    }

}
