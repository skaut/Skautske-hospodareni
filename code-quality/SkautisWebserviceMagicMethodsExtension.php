<?php

class SkautisWebserviceMagicMethodsExtension implements \PHPStan\Reflection\MethodsClassReflectionExtension
{

    public function hasMethod(\PHPStan\Reflection\ClassReflection $classReflection, string $methodName): bool
    {
        return $classReflection->getName() === \Skautis\Wsdl\WebServiceInterface::class; // TODO: Make smarter guess based on WSDL
    }

    public function getMethod(\PHPStan\Reflection\ClassReflection $classReflection, string $methodName): \PHPStan\Reflection\MethodReflection
    {
        return new SkautisWebserviceMethodReflection($classReflection, $methodName);
    }

}
