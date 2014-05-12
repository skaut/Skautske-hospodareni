<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$mockista = new \Mockista\Registry();
$builder = $mockista->createBuilder();
$detail = new stdClass();
$detail->ID = 2;
$builder->getDetail(2)->andReturn($detail);
$builder->getUnitId()->andReturn($detail->ID);
$builder->method(1, 2, 3)->once->andReturn(4);
$skautis = $builder->getMock();




$object = new \Model\UnitService($skautis, $connection = NULL);

Assert::same($object->getDetail()->ID, 2);
Assert::same($object->getDetail(2)->ID, 2);

