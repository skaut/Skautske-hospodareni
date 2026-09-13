<?php

declare(strict_types=1);

namespace CodeQuality;

use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeWithClassName;

/**
 * Brání porovnávání hodnotových objektů referencí — dvě instance se stejným obsahem jsou různé
 * objekty, takže `===` u nich téměř vždy znamená chybu.
 *
 * Enumy jsou vyňaté: každý case je singleton, takže `===` je u nich jediný správný a idiomatický
 * způsob porovnání. Bez této výjimky si pravidlo vynucovalo obcházení přes `->value`.
 */
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

        if ($this->isEnum($left) || $this->isEnum($right)) {
            return [];
        }

        if (
            $left instanceof TypeWithClassName
            && $right instanceof TypeWithClassName
            && $left->getClassName() === $right->getClassName()) {
            return [
                RuleErrorBuilder::message(
                    'Object are compared using ===, '
                    . 'use custom equals() method or spl_object_id if you really wan\'t to check reference'
                )->line($node->getStartLine())->identifier('comparison.objectIdentity')->build()
            ];
        }

        return [];
    }

    private function isEnum(Type $type) : bool
    {
        return ! $type->isEnum()->no();
    }
}
