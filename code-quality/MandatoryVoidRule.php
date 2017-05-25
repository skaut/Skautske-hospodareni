<?php

use PhpParser\Node;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Type\VoidType;

class MandatoryVoidRule implements \PHPStan\Rules\Rule
{

    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if(! ($node instanceof ClassMethod)) {
            return [];
        }

        if($node->name === '__construct') {
            return [];
        }

        $class = $scope->getClassReflection()->getName();
        $method = new ReflectionMethod($class, $node->name);

        if($method->isAbstract()) {
            return [];
        }


        if($this->returnsNullOrNothing($node) && $method->getReturnType() != 'void') {
            return [
                sprintf(
                    'Void method %s::%s() doesn\'t use :void return type.',
                    $class,
                    $node->name
                ),
            ];
        }
        return [];
    }

    private function returnsNullOrNothing(ClassMethod $node)
    {
        $statements = $node->getStmts();

        if($statements === NULL) {
            return TRUE;
        }

        $traverser = new \PhpParser\NodeTraverser();

        $visitor = new ReturnTypeVisitor();
        $traverser->addVisitor($visitor);

        $traverser->traverse($statements);

        return $visitor->returnsNullOrNothing();
    }

}
