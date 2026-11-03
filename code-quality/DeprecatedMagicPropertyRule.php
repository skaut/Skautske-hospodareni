<?php

declare(strict_types=1);

namespace CodeQuality;

use PhpParser\Node;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function preg_match;
use function preg_quote;
use function sprintf;

/**
 * Hlásí čtení magických vlastností, které Nette označuje `@property-deprecated` (např. `$this->presenter`
 * v komponentě). Nette na ně za běhu vyhazuje E_USER_DEPRECATED, které na produkci končí v Sentry.
 *
 * @implements Rule<PropertyFetch>
 */
final class DeprecatedMagicPropertyRule implements Rule
{
    public function getNodeType() : string
    {
        return PropertyFetch::class;
    }

    public function processNode(Node $node, Scope $scope) : array
    {
        if (! $node->name instanceof Identifier) {
            return [];
        }

        $property = $node->name->toString();

        foreach ($scope->getType($node->var)->getObjectClassReflections() as $class) {
            if ($class->hasNativeProperty($property)) {
                continue;
            }

            foreach ([$class, ...$class->getParents()] as $declaringClass) {
                $docComment = $declaringClass->getNativeReflection()->getDocComment();
                if ($docComment === false || preg_match('~@property-deprecated\s+\S+\s+\$'.preg_quote($property, '~').'\b~', $docComment) !== 1) {
                    continue;
                }

                return [
                    RuleErrorBuilder::message(sprintf(
                        'Property %s::$%s is deprecated by Nette, use its getter or setter instead.',
                        $declaringClass->getName(),
                        $property,
                    ))->identifier('property.deprecatedMagic')->build(),
                ];
            }
        }

        return [];
    }
}
