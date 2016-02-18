<?php

namespace Test;

abstract class BaseTestCase extends \Tester\TestCase {

    /** @var \Mockista\Registry */
    protected $mockista;

    protected function setUp() {
        $this->mockista = new \Mockista\Registry();
    }

    protected function tearDown() {
        $this->mockista->assertExpectations();
    }

}
