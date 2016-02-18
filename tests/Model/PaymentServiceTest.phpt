<?php

namespace Test;

use Nette,
    Tester,
    Tester\Assert;

$container = require __DIR__ . '/../bootstrap.php';



class PaymentServiceTest extends BaseTestCase {

    protected $table;

    protected function setUp() {
        parent::setUp();

        $this->mock1 = $this->mockista->create();
        $this->mock1->expects('method')->andReturn(5);
        $this->mock1->expects('method')->once()->with(1, 2, 3)->andReturn(4);

        // or you can use mock builder with nicer syntax
        $builder = $this->mockista->createBuilder();
        $builder->method()->andReturn(5);
        $builder->method(1, 2, 3)->once->andReturn(4);
        $this->mock2 = $builder->getMock();
        
        
        $this->table = $this->mockista->create();
        //$this->table->get(1, 2)->

        // you can create mock of existing class
//        $this->mock3 = $this->mockista->create('ExistingClass', array(
//            'abc' => 1, // you can define return values easily
//            'def' => function ($a) {
//                return $a * 2;
//            }
//        ));
    }

    public function tearDown() {
        # Úklid
    }

    public function testOne() {
        Assert::same(1, 1);
    }

    public function testTwo() {
        //Assert::match(......);
    }

}

$testCase = new PaymentServiceTest();
$testCase->run();
