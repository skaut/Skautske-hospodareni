<?php

declare(strict_types=1);

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * Treat PHPExcel_Worksheet::setCellValue() without $returnCell argument as returning $this (as it does)
 * @see PHPExcel_Worksheet::setCellValue()
 */
class WorksheetDynamicReturnTypeExtension implements \PHPStan\Type\DynamicMethodReturnTypeExtension
{

    public function getClass(): string
    {
        return PHPExcel_Worksheet::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'setCellValue';
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
    {
        $worsheetType = new ObjectType(PHPExcel_Worksheet::class);

        if (count($methodCall->args) < 3) {
            return $worsheetType;
        }

        return TypeCombinator::union($worsheetType, new ObjectType(PHPExcel_Cell::class));
    }

}
