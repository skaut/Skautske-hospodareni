<?php

namespace App\AccountancyModule\PaymentModule;

use Nette\Application\UI\Form;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class GroupPresenter extends BasePresenter {

    /**
     * @var \Model\PaymentService
     */
    protected $model;

    /**
     * @var \Model\MailService
     */
    protected $mail;

    /**
     * výchozí text emailů
     * @var array
     */
    protected $defaultEmails;

    public function __construct(\Model\PaymentService $paymentService, \Model\MailService $mailService) {
        parent::__construct($paymentService);
        $this->model = $paymentService;
        $this->mail = $mailService;
    }

    protected function startup() {
        parent::startup();
        $this->defaultEmails = array(
            "registration" => array(
                "info" => "Dobrý den,\nchtěli bychom vás požádat o úhradu členských příspěvků do našeho skautského střediska. \n<b>Informace k platbě:</b>\nÚčel platby: %name%\nČíslo účtu: %account%\nČástka: %amount% Kč\nDatum splatnosti: %maturity%\nVS: %vs%\nKS: %ks%\n\nPro zrychlení platby jsme připravili QR kód, který lze použít při placení v mobilních aplikacích bank. Použití QR kódu šetří váš čas a snižuje pravděpodobnost překlepu.\n%qrcode%\n\nDěkujeme za včasné uhrazení",
                "demand" => "",
            ),
            "base" => array(
                "info" => "Dobrý den,\nchtěli bychom vás požádat o úhradu. \n<b>Informace k platbě:</b>\nÚčel platby: %name%\nČíslo účtu: %account%\nČástka: %amount% Kč\nDatum splatnosti: %maturity%\nVS: %vs%\nKS: %ks%\n\nPro zrychlení platby jsme připravili QR kód, který lze použít při placení v mobilních aplikacích bank. Použití QR kódu šetří váš čas a snižuje pravděpodobnost překlepu.\n%qrcode%\n\nDěkujeme za včasné uhrazení",
                "demand" => "",
            )
        );
    }

    public function renderDefault() {
        if (!$this->isEditable) {
            $this->flashMessage("Nemáte oprávnění upravovat skupiny plateb", "danger");
            $this->redirect("Payment:default");
        }
        
        $this->template->registration = $this->model->getNewestOpenRegistration();
        $this->template->nadpis = "Založení skupiny plateb";
        $this->template->linkBack = $this->link("Default:");
    }

    public function renderEdit($id) {
        if (!$this->isEditable) {
            $this->flashMessage("Nemáte oprávnění upravovat skupiny plateb", "danger");
            $this->redirect("Payment:default");
        }
        //$this->template->setFile(dirname(__DIR__)."/templates/Group/default.latte");
        $form = $this['groupForm'];
        $form['send']->caption = "Upravit skupinu";
        $this->template->group = $group = $this->model->getGroup($this->aid, $id);
        if (!$group) {
            $this->flashMessage("Skupina nebyla nalezena", "warning");
            $this->redirect("Payment:default");
        }
        $smtp = $this->mail->getSmtpByGroup($id);
        $form->setDefaults(array(
            "label" => $group->label,
            "amount" => $group->amount,
            "maturity" => $group->maturity,
            "ks" => $group->ks,
            "smtp" => isset($smtp->id) && array_key_exists($smtp->id, $form['smtp']->getItems()) ? $smtp->id : NULL,
            "email_info" => $group->email_info,
            //"email_demand" => $group->email_demand,
            "gid" => $group->id,
        ));
        $this->template->nadpis = "Editace skupiny: " . $group->label;
        $this->template->linkBack = $this->link("Payment:detail", array("id" => $id));
    }

    public function actionCreateGroupRegistration($regId) {
        if (!$this->isEditable) {
            $this->flashMessage("Nemáte oprávnění pro založení registrační skupiny", "danger");
            $this->redirect("default");
        }
        $reg = $this->model->getRegistration($regId);
        $groupId = $this->model->createGroup($this->aid, 'registration', $reg->ID, "Registrace " . $reg->Year, $reg->Year . "-01-15", NULL, NULL, $this->defaultEmails['registration']['info'], $this->defaultEmails['registration']['demand']);
        $this->redirect("Registration:massAdd", array("id" => $groupId));
    }

    public function actionCamp($campId) {
        if ($campId === NULL) {
            $this->flashMessage("Nebylo zadáno číslo tábora", "warning");
            $this->redirect("Payment:default");
        }
        if (($group = $this->model->getGroupByCampId($campId))) {
            $this->redirect("Payment:detail", array("id" => $group->id));
        }
        $this->template->nadpis = "Založení skupiny plateb tábora";
        $this->template->linkBack = $this->link(":Accountancy:Camp:Participant:", array("aid" => $campId));
        $camp = $this->model->getCamp($campId);
        $form = $this['groupForm'];
        
        $form->setDefaults(array(
            "type" => "camp",
            "label" => $camp->DisplayName,
            "sisId" => $camp->ID,
        ));
    }

    public function createComponentGroupForm($name) {
        $form = $this->prepareForm($this, $name);
        $form->addText("label", "Název")
                ->setAttribute("class", "form-control")
                ->addRule(Form::FILLED, "Musíte zadat název skupiny");
        $form->addText("amount", "Výchozí částka")
                ->setAttribute("class", "form-control")
                ->addCondition(Form::FILLED)
                ->addRule(Form::FLOAT, "Částka musí být zadaná jako číslo");
        $form->addDatePicker("maturity", "Výchozí splatnost")
                ->setAttribute("class", "form-control");
        $form->addText("ks", "KS", NULL, 10)
                ->setAttribute("class", "form-control")
                ->addCondition(Form::FILLED)
                ->addRule(Form::INTEGER, "Konstantní symbol musí být číslo");
        $form->addSelect("smtp", "Odesílací email", $this->mail->getPairs($this->aid))
                ->setPrompt(\Model\MailService::EMAIL_SENDER);
        $form->addTextArea("email_info", "Informační email", NULL, 6)
                ->setAttribute("class", "form-control")
                ->setDefaultValue($this->defaultEmails['base']['info']);
//        $form->addTextArea("email_demand", "Upomínací email", NULL, 6)
//                ->setAttribute("class", "form-control")
//                ->setDefaultValue($this->defaultEmails['base']['demand']);
        $form->addHidden("type");
        $form->addHidden("gid");
        $form->addHidden("sisId");
        $form->addSubmit('send', "Založit skupinu")->setAttribute("class", "btn btn-primary");
        $form->onSubmit[] = array($this, $name . 'Submitted');
        return $form;
    }

    function groupFormSubmitted(Form $form) {
        if (!$this->isEditable) {
            $this->flashMessage("Nemáte oprávnění pro změny skupin plateb", "danger");
            $this->redirect("default");
        }
        $v = $form->getValues();
        
        if ($v['maturity'] !== NULL && $v['maturity']->format("N") > 5) {
            $form['maturity']->addError("Splatnost nemůže být nastavena na víkend.");
            return;
        }

        if ($v->gid != "") {//EDIT
            $groupId = $v->gid;
            $isUpdate = $this->model->updateGroup($v->gid, array(
                "label" => $v->label,
                "amount" => $v->amount != "" ? $v->amount : NULL,
                "maturity" => $v->maturity,
                "ks" => $v->ks != "" ? $v->ks : NULL,
                "email_info" => $v->email_info,
                //"email_demand" => $v->email_demand,
            ));
            if ($v->smtp !== NULL) {
                $isUpdate = $this->mail->addSmtpGroup($groupId, $v->smtp) || $isUpdate;
            }
            if ($isUpdate) {
                $this->flashMessage("Skupina byla upravena");
            } else {
                $this->flashMessage("Skupina nebyla změněna!", "warning");
            }
            $this->redirect("Payment:detail", array("id" => $v->gid));
        } else {//ADD
            if (($groupId = $this->model->createGroup($this->aid, $v->type != "" ? $v->type : NULL, $v->sisId != "" ? $v->sisId : NULL, $v->label, $v->maturity, $v->ks, $v->amount, $v->email_info))) {//, $v->email_demand
                if ($v->smtp !== NULL) {
                    $this->mail->addSmtpGroup($groupId, $v->smtp);
                }
                $this->flashMessage("Skupina byla založena");
                $this->redirect("Payment:detail", array("id" => $groupId));
            } else {
                $this->flashMessage("Skupinu plateb se nepodařilo založit", "danger");
            }
        }
        $this->redirect("Default:");
    }

}
