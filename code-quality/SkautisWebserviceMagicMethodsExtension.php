<?php

namespace CodeQuality;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Dummy\DummyMethodReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use Skaut\Skautis\Wsdl\WebServiceInterface;

class SkautisWebserviceMagicMethodsExtension implements MethodsClassReflectionExtension
{
    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        return $classReflection->getName() === WebServiceInterface::class; // TODO: Make smarter guess based on WSDL
    }

    public function getMethod(ClassReflection $classReflection, string $methodName) : MethodReflection
    {
        return new DummyMethodReflection($methodName);
    }
}
