<?php

declare(strict_types=1);

use Money\Money;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Type\ObjectType;

class UnusedMoneyMethodResultRule implements Rule
{
    public function getNodeType() : string
    {
        return MethodCall::class;
    }

    public function processNode(Node $node, Scope $scope) : array
    {
        assert($node instanceof MethodCall);

        if (! $scope->isInFirstLevelStatement()) {
            return [];
        }

        $type = $scope->getType($node->var);

        if (! $type instanceof ObjectType || $type->getClassName() !== Money::class) {
            return [];
        }

        return [
            sprintf(
                'Result of Money::%s() is not used. Money object is immutable, so this does nothing!',
                $node->name
            ),
        ];
    }
}
