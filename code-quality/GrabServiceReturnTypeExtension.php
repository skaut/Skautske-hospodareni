<?php

declare(strict_types=1);

namespace CodeQuality;

use IntegrationTester;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/**
 * Otypuje $this->tester->grabService(Foo::class) jako Foo místo generického object.
 *
 * Balíčkový Contributte\Codeception NetteDIModuleType míří na modul, ne na vygenerovaného actora
 * IntegrationTester, na kterém se grabService reálně volá.
 */
final class GrabServiceReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return IntegrationTester::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'grabService';
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
    {
        $args = $methodCall->getArgs();
        if ($args === []) {
            return $methodReflection->getVariants()[0]->getReturnType();
        }

        $argument = $args[0]->value;
        if (! $argument instanceof ClassConstFetch || ! $argument->class instanceof Name) {
            return $methodReflection->getVariants()[0]->getReturnType();
        }

        $class = (string) $argument->class;
        if ($class === 'static' || $class === 'self') {
            return $methodReflection->getVariants()[0]->getReturnType();
        }

        return new ObjectType($class);
    }
}
