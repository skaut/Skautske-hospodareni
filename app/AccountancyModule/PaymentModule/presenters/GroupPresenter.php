<?php

namespace App\AccountancyModule\PaymentModule;

use Nette\Application\UI\Form;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class GroupPresenter extends BasePresenter {

    /**
     *
     * @var \Model\PaymentService
     */
    protected $model;
    protected $defaultEmails;

    public function __construct(\Model\PaymentService $paymentService) {
        parent::__construct($paymentService);
        $this->model = $paymentService;
    }

    protected function startup() {
        parent::startup();
        $this->defaultEmails = array(
            "registration" => array(
                "info" => "Dobrý den,\nchtěli bychom vás požádat o úhradu členských příspěvků do našeho skautského střediska. \n<b>Informace k platbě:</b>\nÚčel platby: %name%\nČíslo účtu: %account%\nČástka: %amount%\nDatum splatnosti: %maturity%\nVS: %vs%\nKS: %ks%\n\nPro zrychlení platby jsme připravili QR kód, který lze použít při placení v mobilních aplikacích bank. Použití QR kódu šetří váš čas a snižuje pravděpodobnost překlepu.\n%qrcode%\n\nDěkujeme za včasné uhrazení",
                "demand" => "",
            ),
            "base" => array(
                "info" => "Dobrý den,\nchtěli bychom vás požádat o úhradu. \n<b>Informace k platbě:</b>\nÚčel platby: %name%\nČíslo účtu: %account%\nČástka: %amount%\nDatum splatnosti: %maturity%\nVS: %vs%\nKS: %ks%\n\nPro zrychlení platby jsme připravili QR kód, který lze použít při placení v mobilních aplikacích bank. Použití QR kódu šetří váš čas a snižuje pravděpodobnost překlepu.\n%qrcode%\n\nDěkujeme za včasné uhrazení",
                "demand" => "",
            )
        );
    }

    public function renderDefault($id = NULL) {
        if (!$this->isEditable) {
            $this->flashMessage("Nemáte oprávnění upravovat skupiny plateb", "error");
            $this->redirect("Payment:default");
        }
        $form = $this['groupForm'];
        $form->addSubmit('send', ($id === NULL ? "Založit" : "Upravit") . ' skupinu')->setAttribute("class", "btn btn-primary");
        if ($id === NULL) {//ADD
            $this->template->registration = $this->model->getNewestOpenRegistration();
            $this->template->nadpis = "Založení skupiny plateb";
            $this->template->linkBack = $this->link("Default:");
        } else {//EDIT
            $this->template->group = $group = $this->model->getGroup($this->aid, $id);
            $form->setDefaults(array(
                "label" => $group->label,
                "amount" => $group->amount,
                "maturity" => $group->maturity,
                "ks" => $group->ks,
                "email_info" => $group->email_info,
                "email_demand" => $group->email_demand,
                "gid" => $group->id,
            ));
            $this->template->nadpis = "Editace skupiny: " . $group->label;
            $this->template->linkBack = $this->link("Payment:detail", array("id" => $id));
        }
    }

    public function actionCreateGroupRegistration($regId) {
        if (!$this->isEditable) {
            $this->flashMessage("Nemáte oprávnění pro založení registrace", "fail");
            $this->redirect("default");
        }
        $reg = $this->model->getRegistration($regId);
        $groupId = $this->model->createGroup($this->objectId, 'registration', $reg->ID, "Registrace " . $reg->Year, $reg->Year . "-01-15", NULL, NULL, $this->defaultEmails['registration']['info'], $this->defaultEmails['registration']['demand']);
        $this->redirect("Registration:massAdd", array("id" => $groupId));
    }

    public function createComponentGroupForm($name) {
        $form = new Form($this, $name);
        $form->addText("label", "Název")
                ->addRule(Form::FILLED, "Musíte zadat název skupiny");
        $form->addText("amount", "Výchozí částka")
                ->addCondition(Form::FILLED)
                ->addRule(Form::FLOAT, "Částka musí být zadaná jako číslo");
        $form->addDatePicker("maturity", "Výchozí splatnost");
        $form->addText("ks", "KS", NULL, 10)
                ->addCondition(Form::FILLED)
                ->addRule(Form::INTEGER, "Konstantní symbol musí být číslo");
        $form->addTextArea("email_info", "Informační email", NULL, 6)
                ->setDefaultValue($this->defaultEmails['base']['info'])
                ->setAttribute("class", "input-xxlarge");
        $form->addTextArea("email_demand", "Upomínací email", NULL, 6)
                ->setDefaultValue($this->defaultEmails['base']['demand'])
                ->setAttribute("class", "input-xxlarge");
        $form->addHidden("gid");
        $form->onSubmit[] = array($this, $name . 'Submitted');
        return $form;
    }

    function groupFormSubmitted(Form $form) {
        if (!$this->isEditable) {
            $this->flashMessage("Nemáte oprávnění pro změny skupin plateb", "fail");
            $this->redirect("default");
        }
        $v = $form->getValues();
        if ($v->gid != "") {//EDIT
            if ($this->model->updateGroup($v->gid, array(
                        "label" => $v->label,
                        "amount" => $v->amount != "" ? $v->amount : NULL,
                        "maturity" => $v->maturity,
                        "ks" => $v->ks != "" ? $v->ks : NULL,
                        "email_info" => $v->email_info,
                        "email_demand" => $v->email_demand,
                    ))) {
                $this->flashMessage("Skupina byla upravena");
            } else {
                $this->flashMessage("Skupina nebyla změněna!", "warning");
            }
            $this->redirect("Payment:detail", array("id" => $v->gid));
        } else {//ADD
            if (($groupId = $this->model->createGroup($this->objectId, NULL, NULL, $v->label, $v->maturity, $v->ks, $v->amount))) {
                $this->flashMessage("Skupina byla založena");
                $this->redirect("Payment:detail", array("id" => $groupId));
            } else {
                $this->flashMessage("Skupinu plateb se nepodařilo založit", "fail");
            }
        }
        $this->redirect("Default:");
    }

}
