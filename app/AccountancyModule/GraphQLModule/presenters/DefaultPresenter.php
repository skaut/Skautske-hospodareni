<?php

declare(strict_types=1);

namespace App\AccountancyModule\GraphQLModule;

use Nette\Application\IPresenter;
use Nette\Application\IResponse;
use Nette\Application\Request;
use Nette\Application\Responses\JsonResponse;
use Nette\Http\IRequest;
use Youshido\GraphQL\Execution\Processor;
use Youshido\GraphQL\Field\FieldInterface;
use Youshido\GraphQL\Schema\Schema;
use Youshido\GraphQL\Type\Object\ObjectType;

final class DefaultPresenter implements IPresenter
{

    /** @var FieldInterface[] */
    private $fields;

    /** @var FieldInterface[] */
    private $mutations;

    /** @var IRequest */
    private $httpRequest;

    /**
     * @param FieldInterface[] $fields
     * @param FieldInterface[] $mutations
     */
    public function __construct(
        array $fields,
        array $mutations,
        IRequest $httpRequest
    )
    {
        $this->fields = $fields;
        $this->mutations = $mutations;
        $this->httpRequest = $httpRequest;
    }

    public function run(Request $request): IResponse
    {
        $rootQueryType = new ObjectType([
            'name' => 'Root',
            'fields' => $this->fields,
        ]);

        $rootMutationType = new ObjectType([
            'name' => 'RootMutationType',
            'fields' => $this->mutations,
        ]);

        $schema = new Schema([
            'query' => $rootQueryType,
            'mutation' => $rootMutationType,
        ]);


        $processor = new Processor($schema);

        $request = \json_decode($this->httpRequest->getRawBody(), TRUE);

        $processor->processPayload($request['query'], $request['variables']);

        return new JsonResponse($processor->getResponseData());
    }

}
