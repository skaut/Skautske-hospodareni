<?php

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Nette\NetteObjectPropertyReflection;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Type\ObjectType;
use Skautis\Skautis;
use Skautis\Wsdl\WebServiceInterface;

class SkautisAliasesExtension implements PropertiesClassReflectionExtension
{

    private const ALIASES = [
        "user",
        "usr",
        "org",
        "app",
        "event",
        "events",
        "ApplicationManagement",
        "ContentManagement",
        "Evaluation",
        "Events",
        "Exports",
        "GoogleApps",
        "Journal",
        "Material",
        "Message",
        "OrganizationUnit",
        "Power",
        "Reports",
        "Summary",
        "Task",
        "Telephony",
        "UserManagement",
        "Vivant",
        "Welcome",
    ];

    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        return $classReflection->getName() === Skautis::class
            && in_array($propertyName, self::ALIASES, TRUE);
    }


    public function getProperty(ClassReflection $classReflection, string $propertyName): PropertyReflection
    {
        return new NetteObjectPropertyReflection(
            $classReflection,
            new ObjectType(WebServiceInterface::class)
        ); // It works pretty much same as Nette\Object properties, so...
    }

}
