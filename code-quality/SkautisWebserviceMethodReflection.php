<?php

use PHPStan\Reflection\ClassReflection;

class SkautisWebserviceMethodReflection implements \PHPStan\Reflection\MethodReflection
{

    /** @var ClassReflection  */
    private $declaringClass;

    /** @var string */
    private $name;


    public function __construct(ClassReflection $declaringClass, $name)
    {
        $this->declaringClass = $declaringClass;
        $this->name = $name;
    }


    public function getDeclaringClass(): ClassReflection
    {
        return $this->declaringClass;
    }


    public function isStatic(): bool
    {
        return FALSE;
    }


    public function isPrivate(): bool
    {
        return FALSE;
    }


    public function isPublic(): bool
    {
        return TRUE;
    }


    public function getPrototype(): \PHPStan\Reflection\MethodReflection
    {
        return $this;
    }


    public function getName(): string
    {
        return $this->name;
    }


    public function getParameters(): array
    {
        return [];
    }


    public function isVariadic(): bool
    {
        return TRUE;
    }


    public function getReturnType(): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\MixedType();
    }


}
