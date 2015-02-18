<?php

namespace App\AccountancyModule\PaymentModule;

use Nette\Application\UI\Form;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class MailPresenter extends BasePresenter {

    /**
     *
     * @var \Model\MailService
     */
    protected $model;

    public function __construct(\Model\PaymentService $paymentService, \Model\MailService $mailService) {
        parent::__construct($paymentService);
        $this->model = $mailService;
    }

    public function renderDefault($aid) {
        if (!$this->isEditable) {
            $this->flashMessage("Nemáte oprávnění přistupovat ke správě emailů", "danger");
            $this->redirect("Payment:default");
        }
        $this->template->list = $this->model->getAll($this->aid);
    }

//    public function renderDetail($id){
//        if (!$this->isEditable) {
//            $this->flashMessage("Nemáte oprávnění přistupovat ke správě emailů", "danger");
//            $this->redirect("Payment:default");
//        }
//        $this->template->detail = $this->model->get($id);
//    }

    public function handleEdit($id) {
        if (!$this->isEditable) {
            $this->flashMessage("Nemáte oprávnění měnit smtp", "danger");
            $this->redirect("Payment:default");
        }
    }

    public function handleRemove($id) {
        if (!$this->isEditable) {
            $this->flashMessage("Nemáte oprávnění mazat smtp", "danger");
            $this->redirect("Payment:default");
        }
        $this->model->removeSmtp($this->aid, $id);
    }
    
     function createComponentFormCreate($name) {
        $form = $this->prepareForm($this, $name);
        $form->addText("host", "Host")
                ->addRule(Form::FILLED, "Musíte vyplnit pole host.")
                ->getControlPrototype()->placeholder("např. smtp.gmail.com");
        $form->addText("username", "Už. jméno")
                ->addRule(Form::FILLED, "Musíte vyplnit uživatelské jméno.")
                ->getControlPrototype()->placeholder("např. platby@stredisko.cz");
        $form->addText("password", "Heslo")
                ->addRule(Form::FILLED, "Musíte vyplnit heslo.");
        $form->addSelect("secure", "Zabezpečení", array("ssl"=>"ssl", "tsl"=>"tsl"));
        $form->addSubmit('send', 'Založit')
                ->setAttribute("class", "btn btn-primary");
        $form->onSuccess[] = array($this, $name . 'Submitted');
        return $form;
    }

    function formCreateSubmitted(Form $form) {
        if (!$this->isEditable) {
            $this->flashMessage("Nemáte oprávnění přidávat smtp", "danger");
            $this->redirect("Payment:default");
        }
        $v = $form->getValues();
        
        if($this->model->addSmtp($this->aid, $v->host, $v->username, $v->password, $v->secure)){
            $this->flashMessage("SMTP účet byl přidán");
        } else {
            $this->flashMessage("SMTP účet se nepodařilo přidat", "danger");
        }
        $this->redirect("this");
    }

}
