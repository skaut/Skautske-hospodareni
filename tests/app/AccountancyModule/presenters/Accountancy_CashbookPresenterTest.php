<?php

require_once 'PHPUnit/Autoload.php';

class Accountancy_CashbookPresenterTest extends PHPUnit_Framework_TestCase {

    /**
     * @var Accountancy_CashbookPresenter
     */
    protected $object;

    protected function setUp() {
        $this->object = new Accountancy_CashbookPresenter(Environment::getContext());
        $this->object->user->setAuthenticator(new SkautISAuthenticator);
        $this->object->user->login(array(array("ID"=>1, "DisplayName"=>"UnitTest")));
    }

    /**
     * @covers Accountancy_CashbookPresenter::renderDefault
     * @todo Implement testRenderDefault().
     */
    public function testRenderDefault() {
        $requestData = array(
            'action' => 'default', // přistupujeme k výchozí action
            'aid' => "22",
        );
        $request = new PresenterRequest('Accountancy_Cashbook', 'get', $requestData); // vytváříme request
        $response = $this->object->run($request); // spouštíme presenter
        var_dump($response->source);
        $this->assertInstanceOf("AppForm", $this->object['formOutAdd']);
        
    }

    /**
     * @covers Accountancy_CashbookPresenter::renderEdit
     * @todo Implement testRenderEdit().
     */
    public function testRenderEdit() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Accountancy_CashbookPresenter::actionExport
     * @todo Implement testActionExport().
     */
    public function testActionExport() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Accountancy_CashbookPresenter::actionPrint
     * @todo Implement testActionPrint().
     */
    public function testActionPrint() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Accountancy_CashbookPresenter::handleRemove
     * @todo Implement testHandleRemove().
     */
    public function testHandleRemove() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Accountancy_CashbookPresenter::createComponentFormMass
     * @todo Implement testCreateComponentFormMass().
     */
    public function testCreateComponentFormMass() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Accountancy_CashbookPresenter::massPrintSubmitted
     * @todo Implement testMassPrintSubmitted().
     */
    public function testMassPrintSubmitted() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Accountancy_CashbookPresenter::createComponentFormOutAdd
     * @todo Implement testCreateComponentFormOutAdd().
     */
    public function testCreateComponentFormOutAdd() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Accountancy_CashbookPresenter::createComponentFormOutEdit
     * @todo Implement testCreateComponentFormOutEdit().
     */
    public function testCreateComponentFormOutEdit() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Accountancy_CashbookPresenter::createComponentFormInAdd
     * @todo Implement testCreateComponentFormInAdd().
     */
    public function testCreateComponentFormInAdd() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Accountancy_CashbookPresenter::createComponentFormInEdit
     * @todo Implement testCreateComponentFormInEdit().
     */
    public function testCreateComponentFormInEdit() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Accountancy_CashbookPresenter::formAddSubmitted
     * @todo Implement testFormAddSubmitted().
     */
    public function testFormAddSubmitted() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Accountancy_CashbookPresenter::formEditSubmitted
     * @todo Implement testFormEditSubmitted().
     */
    public function testFormEditSubmitted() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

}

?>
