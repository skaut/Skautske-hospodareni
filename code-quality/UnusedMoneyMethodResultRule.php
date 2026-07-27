<?php

declare(strict_types=1);

use Money\Money;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

class UnusedMoneyMethodResultRule implements Rule
{
    public function getNodeType() : string
    {
        return MethodCall::class;
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope) : array
    {
        if (! ($node instanceof MethodCall)) {
            throw new \LogicException('Assertion failed.');
        }
        if (! $scope->isInFirstLevelStatement()) {
            return [];
        }

        $type = $scope->getType($node->var);

        if (! $type instanceof ObjectType || $type->getClassName() !== Money::class) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Result of Money::%s() is not used. Money object is immutable, so this does nothing!',
                    $node->name
                )
            )->identifier('money.unusedResult')->build(),
        ];
    }
}
