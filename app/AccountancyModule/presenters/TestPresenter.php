<?php

/**
 * @author sinacek
 */
class Accountancy_TestPresenter extends Accountancy_BasePresenter {

    public $wsdl;
    protected $service;

    protected function startup() {
        parent::startup();
        if(!$this->context->parameters['skautisTestMode'])//funguje pouze v testovacím režimu
            $this->redirect (":Default:");

        /**
         * @var SkautisService
         */
        $this->service = SkautIS::getInstance();
        $this->service->nette = true;

        $post = $this->request->post;
        if (isset($post['skautIS_Token'])) {
            $this->service->init($post);
        }
        $this->template->skautIsAppId = $this->service->getAppId();
        if (!$this->service->isLoggedIn() && $this->action != "default") {
            $this->accessFail();
            $this->flashMessage("Chybí aktivní přihlášení do skautISu", "fail");
            $this->redirect("default");
        }
        
        //$ret = $this->service->user->UserDetail("ID");
        //dump($ret);
        //die();

        //$this->wsdl = array("organization" => "Organization", "user" => "User", "event" => "Event");
        $this->wsdl = $this->service->getWsdlList();
    }

    public function renderDefault() {
        $this->template->response = Debugger::dump($this->session->getSection("sisTest")->response, TRUE);
    }

    public function renderTest2() {
        //$data = simplexml_load_file(WWW_DIR. '/files/OrganizationUnit.xml');
        $this->template->data = $data;
    }

    public function createComponentTestForm($name) {
        $form = new AppForm($this, $name);
        $form->getElementPrototype()->class("aja");
        $form->addSelect("wsdl", "WSDL", $this->wsdl)
                ->addRule(Form::FILLED, "Musís vybrat WSDL");
        $form->addText("service", "Service")
                ->setDefaultValue("unitAll")
                ->addRule(FORM::FILLED, "Vypln service");
        $form->addText("cover", "Obal", 40)
                ->getControlPrototype()
                ->placeholder("pouze pokud není ve formátu <název funkce>Input (např u PersonInsert)");
        $form->addTextArea("args", "parametry")
                ->setDefaultValue("ID_UnitParent : 24404");

        $form->addSubmit('send', 'Odeslat');
        $form->onSuccess[] = array($this, $name . 'Submitted');

        $sess = $this->session->getSection("sisTest");
        if (isset($sess->defaults))
            $form->setDefaults($sess->defaults);

        return $form;
    }

    public function testFormSubmitted(AppForm $form) {
        $sess = &$this->session->getSection("sisTest");

        $values = $form->getValues();
        $uservice = new UserService();
        if (!$this->service->isLoggedIn()) {
            $this->flashMessage("Nemáte platné přihlášení do skautISu.", "fail");
            $this->redirect(":Auth:");
        }
        $sess->defaults = $values;

        $args = Neon::decode($values['args']);

        //dump($args);die();
        $cover = trim($values['cover']);
        if ($cover == "")
            $cover = NULL;
        try {
            $ret = $this->service->{$values['wsdl']}->{$values["service"]}($args, $cover);
        } catch (Exception $e) {
            //dump($e);die();
            $this->flashMessage($e->getMessage(), "fail");
            $sess->response = $e->getMessage();
            $this->redirect("this");
        }

        
        //$ret = $this->service->callFunction($wsdl, $values["service"], $args, $cover);

        $sess->response = $ret;

        $this->flashMessage("Odesláno: " . $values["service"] . " (" . str_replace(array("\n"), "", $values['args']) . ")");


        if (!$this->isAjax())
            $this->redirect('this');
        else {
            $this->invalidateControl('flash');
            $this->invalidateControl('form');
            $this->invalidateControl('testResponse');
        }
    }

}
