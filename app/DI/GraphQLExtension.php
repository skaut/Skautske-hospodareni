<?php

declare(strict_types=1);

namespace App\AccountancyModule\DI;

use App\AccountancyModule\GraphQLModule\DefaultPresenter;
use Nette\DI\CompilerExtension;

class GraphQLExtension extends CompilerExtension
{

    private const TAG_FIELD = 'graphql.field';
    private const TAG_MUTATION = 'graphql.mutation';

    public function beforeCompile(): void
    {
        $builder = $this->getContainerBuilder();
        $presenter = $builder->getDefinitionByType(DefaultPresenter::class);

        $presenter->setArguments([
            $this->getServicesByTag(self::TAG_FIELD),
            $this->getServicesByTag(self::TAG_MUTATION),
        ]);
    }

    /**
     * @return string[]
     */
    private function getServicesByTag(string $tag): array
    {
        $services = $this->getContainerBuilder()->findByTag($tag);

        return array_map(function(string $serviceName): string {
            return '@' . $serviceName;
        }, array_keys($services));
    }

}
