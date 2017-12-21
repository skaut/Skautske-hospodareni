<?php

namespace App\AccountancyModule\GraphQLModule;

use App\AccountancyModule\GraphQLModule\Fields\UserField;
use App\AccountancyModule\GraphQLModule\Mutations\ChangeRoleMutation;
use App\AccountancyModule\GraphQLModule\Schema\UserType;
use Nette\Application\UI\Presenter;
use Youshido\GraphQL\Execution\Processor;
use Youshido\GraphQL\Schema\Schema;
use Youshido\GraphQL\Type\Object\ObjectType;

class DefaultPresenter extends Presenter
{

    /** @var UserField */
    private $userField;

    /** @var ChangeRoleMutation */
    private $changeRoleMutation;

    public function __construct(UserField $userField, ChangeRoleMutation $changeRoleMutation)
    {
        parent::__construct();
        $this->userField = $userField;
        $this->changeRoleMutation = $changeRoleMutation;
    }

    public function renderDefault(): void
    {
        $rootQueryType = new ObjectType([
            'name' => 'Root',
            'fields' => [
                'user' => $this->userField,
            ]
        ]);

        $rootMutationType = new ObjectType([
            'name' => 'RootMutationType',
            'fields' => [
                $this->changeRoleMutation,
            ]
        ]);

        $schema = new Schema([
            'query' => $rootQueryType,
            'mutation' => $rootMutationType,
        ]);


        $processor = new Processor($schema);

        $request = \json_decode($this->getHttpRequest()->getRawBody(), TRUE);

        $processor->processPayload($request['query'], $request['variables']);
        $this->sendJson($processor->getResponseData());
    }

}
