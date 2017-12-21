<?php

namespace App\AccountancyModule\GraphQLModule;

use App\AccountancyModule\GraphQLModule\Fields\UserField;
use App\AccountancyModule\GraphQLModule\Schema\UserType;
use Nette\Application\UI\Presenter;
use Youshido\GraphQL\Execution\Processor;
use Youshido\GraphQL\Schema\Schema;
use Youshido\GraphQL\Type\Object\ObjectType;

class DefaultPresenter extends Presenter
{

    /** @var UserField */
    private $userField;

    public function __construct(UserField $userField)
    {
        parent::__construct();
        $this->userField = $userField;
    }

    public function renderDefault(): void
    {
        $rootQueryType = new ObjectType([
            'name' => 'Root',
            'fields' => [
                'user' => $this->userField,
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
