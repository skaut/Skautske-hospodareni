<?php

declare(strict_types=1);

use Mockery as m;

class DtoTest extends \Codeception\Test\Unit
{

    private const CLASSES = [
        \Model\DTO\Cashbook\Chit::class,
        \Model\DTO\Cashbook\Category::class,
    ];

    /**
     * @dataProvider getDTOClasses
     */
    public function testDTO(string $className): void
    {
        $class = new ReflectionClass($className);

        $getters = $this->getGetters($class);

        $constructor = $class->getConstructor();

        $constuctorParameters = [];

        foreach ($constructor->getParameters() as $parameter) {
            $constuctorParameters[$parameter->getName()] = $this->getFakeData($parameter);
        }

        $dto = new $className(...array_values($constuctorParameters));

        foreach ($getters as $methodName => $parameterName) {
            $this->assertSame($constuctorParameters[$parameterName], $dto->{$methodName}());
        }
    }

    public function getDTOClasses(): array
    {
        return array_map(function (string $class) { return [$class]; }, self::CLASSES);
    }

    private function getProperty(string $methodName): string
    {
        $validPrefixes = ['is', 'get'];

        foreach ($validPrefixes as $prefix) {
            if (strpos($methodName, $prefix) === 0) {
                return lcfirst(substr($methodName, strlen($prefix)));
            }
        }

        throw new InvalidArgumentException("Invalid getter '$methodName'");
    }

    private function getGetters(ReflectionClass $class): array
    {
        $traitMethods = [];

        foreach($class->getTraits() as $trait) {
            foreach ($trait->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                $traitMethods[] = $method->getName();
            }
        }

        $getters = [];

        foreach ($class->getMethods() as $method) {
            $methodName = $method->getName();

            if (in_array($methodName, $traitMethods, TRUE)) {
                continue;
            }

            try {
                $getters[$methodName] = $this->getProperty($methodName);
            } catch (InvalidArgumentException $e) {
                // do not test different methods
            }
        }

        return $getters;
    }

    private function getFakeData(ReflectionParameter $parameter)
    {
        $type = $parameter->getType();

        if ($type === NULL) {
            throw new RuntimeException(sprintf('Parameter %s has no typehint!', $parameter->getName()));
        }

        if ( ! $type->isBuiltin()) {
            return (new ReflectionClass($type->getName()))->newInstanceWithoutConstructor();
        }

        $fakeData = [
            'int' => 666,
            'float' => 666.6,
            'string' => 'Test string',
            'bool' => TRUE,
            'array' => ['one', 'two', 'three'],
            'callable' => function () {},
            'iterable' => [],
        ];

        return $fakeData[$type->getName()];
    }

}
