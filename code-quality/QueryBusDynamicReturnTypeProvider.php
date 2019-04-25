<?php

declare(strict_types=1);

namespace CodeQuality;

use eGen\MessageBus\Bus\QueryBus;
use Nette\Loaders\RobotLoader;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Broker\Broker;
use PHPStan\Reflection\BrokerAwareExtension;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeWithClassName;

final class QueryBusDynamicReturnTypeProvider implements DynamicMethodReturnTypeExtension, BrokerAwareExtension
{
    /** @var array<string, Type> query class => type */
    private $returnTypes;

    /** @var Broker */
    private $broker;

    /** @var array<string> */
    private $autoloadDirectories;

    /** @var string */
    private $tempDir;

    /** @var string */
    private $queryHandlerClassRegex;

    /**
     * @param array<string> $autoloadDirectories
     * @param string $queryHandlerClassRegex regex for FQCN of query handlers
     */
    public function __construct(array $autoloadDirectories, string $tempDir, string $queryHandlerClassRegex)
    {
        $this->autoloadDirectories    = $autoloadDirectories;
        $this->tempDir                = $tempDir;
        $this->queryHandlerClassRegex = $queryHandlerClassRegex;
    }

    public function setBroker(Broker $broker) : void
    {
        $this->broker = $broker;
    }

    public function getClass() : string
    {
        return QueryBus::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection) : bool
    {
        return $methodReflection->getName() === 'handle';
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ) : Type {
        $this->analyzeReturnTypes($scope);

        $queryType = $scope->getType($methodCall->args[0]->value);

        if (! $queryType instanceof TypeWithClassName) {
            return new MixedType();
        }

        return $this->returnTypes[$queryType->getClassName()] ?? new NeverType();
    }

    private function analyzeReturnTypes(Scope $scope) : void
    {
        if ($this->returnTypes !== null) {
            return;
        }

        $this->returnTypes = [];

        foreach ($this->getQueryHandlerClasses() as $className) {
            $classReflection = $this->broker->getClass($className);

            if ($classReflection->isAbstract()) {
                continue;
            }

            $methodReflections = $classReflection->getNativeReflection()->getMethods(\ReflectionMethod::IS_PUBLIC);

            foreach ($methodReflections as $methodReflection) {
                $method = $classReflection->getMethod($methodReflection->getName(), $scope);

                if ($methodReflection->isConstructor()) {
                    continue;
                }

                foreach ($method->getVariants() as $acceptor) {
                    $parameters = $acceptor->getParameters();

                    if (count($parameters) !== 1) {
                        continue;
                    }

                    $queryParameterType = $parameters[0]->getType();

                    if (! $queryParameterType instanceof TypeWithClassName) {
                        continue;
                    }

                    $this->returnTypes[$queryParameterType->getClassName()] = $acceptor->getReturnType();
                }
            }
        }
    }

    /**
     * @return array<string>
     */
    private function getQueryHandlerClasses() : array
    {
        $loader = (new RobotLoader())
            ->setTempDirectory($this->tempDir)
            ->setAutoRefresh(true);

        foreach ($this->autoloadDirectories as $directory) {
            $loader->addDirectory($directory);
        }

        $loader->rebuild();

        return array_filter(
            array_keys($loader->getIndexedClasses()),
            function (string $className) : bool {
                return preg_match($this->queryHandlerClassRegex, $className) === 1;
            }
        );
    }
}
