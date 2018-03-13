<?php

declare(strict_types=1);

namespace CodeQuality;

use Nette\Application\UI\Presenter;
use Nette\Http\Session;
use Nette\Http\SessionSection;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

class PresenterDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return Presenter::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'getSession';
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
    {
        $sectionArgument = $methodCall->args[0]->value ?? NULL;

        if ($sectionArgument === NULL || $scope->getType($sectionArgument) instanceof NullType) {
            return new ObjectType(Session::class);
        }

        return new ObjectType(SessionSection::class);
    }

}
