<?php

declare(strict_types=1);

namespace CodeQuality;

use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeWithClassName;

final class ObjectIdentityComparisonRule implements Rule
{
    public function getNodeType() : string
    {
        return BinaryOp::class;
    }

    public function processNode(Node $node, Scope $scope) : array
    {
        if (! $node instanceof BinaryOp\Identical && ! $node instanceof BinaryOp\NotIdentical) {
            return [];
        }

        $left = TypeCombinator::removeNull($scope->getType($node->left));
        $right = TypeCombinator::removeNull($scope->getType($node->right));

        if (
            $left instanceof TypeWithClassName
            && $right instanceof TypeWithClassName
            && $left->getClassName() === $right->getClassName()) {
            return [
                RuleErrorBuilder::message(
                    'Object are compared using ===, '
                    . 'use custom equals() method or spl_object_id if you really wan\'t to check reference'
                )->line($node->getLine())->build()
            ];
        }

        return [];
    }
}
