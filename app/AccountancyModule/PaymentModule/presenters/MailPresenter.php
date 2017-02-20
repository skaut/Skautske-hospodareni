<?php

namespace App\AccountancyModule\PaymentModule;

use Nette\Application\UI\Form;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class MailPresenter extends BasePresenter
{

    /** @var \Model\MailService */
    protected $model;

    public function __construct(\Model\PaymentService $paymentService, \Model\MailService $mailService)
    {
        parent::__construct($paymentService);
        $this->model = $mailService;
    }

    public function renderDefault($aid) : void
    {
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

    public function handleEdit($id) : void
    {
        if (!$this->isEditable) {
            $this->flashMessage("Nemáte oprávnění měnit smtp", "danger");
            $this->redirect("Payment:default");
        }
    }

    public function handleRemove($id) : void
    {
        if (!$this->isEditable) {
            $this->flashMessage("Nemáte oprávnění mazat smtp", "danger");
            $this->redirect("Payment:default");
        }
        $this->model->removeSmtp($this->aid, $id);
    }

    protected function createComponentFormCreate($name) : Form
    {
        $form = $this->prepareForm($this, $name);
        $form->addText("host", "Host")
            ->addRule(Form::FILLED, "Musíte vyplnit pole host.")
            ->getControlPrototype()->placeholder("např. smtp.gmail.com");
        $form->addText("username", "Už. jméno")
            ->addRule(Form::FILLED, "Musíte vyplnit uživatelské jméno.")
            ->getControlPrototype()->placeholder("např. platby@stredisko.cz");
        $form->addText("password", "Heslo")
            ->addRule(Form::FILLED, "Musíte vyplnit heslo.");
        $form->addSelect("secure", "Zabezpečení", ["ssl" => "ssl", "tsl" => "tsl"]);
        $form->addSubmit('send', 'Založit')
            ->setAttribute("class", "btn btn-primary");

        $form->onSuccess[] = function(Form $form) : void {
            $this->formCreateSubmitted($form);
        };

        return $form;
    }

    private function formCreateSubmitted(Form $form) : void
    {
        if (!$this->isEditable) {
            $this->flashMessage("Nemáte oprávnění přidávat smtp", "danger");
            $this->redirect("Payment:default");
        }
        $v = $form->getValues();

        if ($this->model->addSmtp($this->aid, $v->host, $v->username, $v->password, $v->secure)) {
            $this->flashMessage("SMTP účet byl přidán");
        } else {
            $this->flashMessage("SMTP účet se nepodařilo přidat", "danger");
        }
        $this->redirect("this");
    }

}
