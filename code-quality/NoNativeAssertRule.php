<?php

declare(strict_types=1);

namespace CodeQuality;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;

class NoNativeAssertRule implements Rule
{
    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    /**
     * @return list<string>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $normalizedFile = str_replace("\\", "/", $scope->getFile());
        if (str_contains($normalizedFile, "/tests/")) {
            return [];
        }

        if (! ($node instanceof FuncCall)) {
            throw new \LogicException('Unexpected node type.');
        }

        if (! ($node->name instanceof Name)) {
            return [];
        }

        if (strtolower((string) $node->name) !== 'assert') {
            return [];
        }

        return [
            'Do not use native assert() in application code. Use an explicit guard that throws an exception.',
        ];
    }
}
