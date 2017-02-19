<?php

namespace App\AccountancyModule\PaymentModule;

use Nette\Application\UI\Form;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class GroupPresenter extends BasePresenter
{

    /** @var \Model\PaymentService */
    private $model;

    /** @var \Model\MailService */
    private $mail;

    /** @var \Model\EventService */
    private $camp;

    /**
     * výchozí text emailů
     * @var array
     */
    protected $defaultEmails;

    public function __construct(\Model\PaymentService $paymentService, \Model\MailService $mailService)
    {
        parent::__construct($paymentService);
        $this->model = $paymentService;
        $this->mail = $mailService;
    }

    protected function startup() : void
    {
        parent::startup();
        $this->camp = $this->context->getService("campService");
        $this->defaultEmails = [
            "registration" => [
                "info" => "Dobrý den,\nchtěli bychom vás požádat o úhradu členských příspěvků do našeho skautského střediska. \n<b>Informace k platbě:</b>\nÚčel platby: %name%\nČíslo účtu: %account%\nČástka: %amount% Kč\nDatum splatnosti: %maturity%\nVS: %vs%\nKS: %ks%\n\nPro zrychlení platby jsme připravili QR kód, který lze použít při placení v mobilních aplikacích bank. Použití QR kódu šetří váš čas a snižuje pravděpodobnost překlepu.\n%qrcode%\n\nDěkujeme za včasné uhrazení",
            ],
            "base" => [
                "info" => "Dobrý den,\nchtěli bychom vás požádat o úhradu. \n<b>Informace k platbě:</b>\nÚčel platby: %name%\nČíslo účtu: %account%\nČástka: %amount% Kč\nDatum splatnosti: %maturity%\nVS: %vs%\nKS: %ks%\n\nPro zrychlení platby jsme připravili QR kód, který lze použít při placení v mobilních aplikacích bank. Použití QR kódu šetří váš čas a snižuje pravděpodobnost překlepu.\n%qrcode%\n\nDěkujeme za včasné uhrazení",
            ]
        ];
    }

    public function actionDefault($type = NULL) : void
    {
        if (!$this->isEditable) {
            $this->flashMessage("Nemáte oprávnění upravovat skupiny plateb", "danger");
            $this->redirect("Payment:default");
        }

        if ($type == "camp") {
            $allCamps = $this->camp->event->getAll(date("Y"));
            $camps = [];
            foreach (array_diff_key($allCamps, (array)$this->model->getCampIds()) as $id => $c) {
                $camps[$id] = $c['DisplayName'];
            }
            $this['groupForm']['sisId']->caption = "Tábor";
            $this['groupForm']['type']->setDefaultValue("camp");
            $this['groupForm']['sisId']
                ->addRule(Form::FILLED, "Vyberte tábor kterého se skupina týká!")
                ->setPrompt("Vyberte tábor")
                ->setHtmlId("camp-select")
                ->setItems($camps);
            $this->template->nadpis = "Založení skupiny plateb tábora";
        } elseif ($type == "registration") {
            if (!($reg = $this->model->getNewestRegistration())) {
                $this->flashMessage("Nemáte založenou žádnou otevřenou registraci", "warning");
                $this->redirect("Payment:default");
            }
            $this['groupForm']['type']->setDefaultValue("registration");
            unset($this['groupForm']['amount']);
            unset($this['groupForm']['sisId']);
            $this['groupForm']->addHidden("sisId", $reg['ID']);
            $this['groupForm']->setDefaults([
                "label" => "Registrace " . $reg['Year'], $reg['Year'] . "-01-15",
            ]);
            $this->template->nadpis = "Založení skupiny plateb pro registraci";

        } else {//obecná skupina
            unset($this['groupForm']['sisId']);
            $this->template->nadpis = "Založení skupiny plateb";
        }

        //$this->template->registration = $this->model->getNewestOpenRegistration();
        $this->template->linkBack = $this->link("Default:");
    }

    public function renderEdit($id) : void
    {
        if (!$this->isEditable) {
            $this->flashMessage("Nemáte oprávnění upravovat skupiny plateb", "danger");
            $this->redirect("Payment:default");
        }
        //$this->template->setFile(dirname(__DIR__)."/templates/Group/default.latte");
        $form = $this['groupForm'];
        unset($form['sisId']);
        $form['send']->caption = "Upravit skupinu";
        $this->template->group = $group = $this->model->getGroup($this->aid, $id);
        if (!$group) {
            $this->flashMessage("Skupina nebyla nalezena", "warning");
            $this->redirect("Payment:default");
        }
        $smtp = $this->mail->getSmtpByGroup($id);
        $form->setDefaults([
            "label" => $group->label,
            "amount" => $group->amount,
            "maturity" => $group->maturity,
            "ks" => $group->ks,
            "smtp" => isset($smtp->id) && array_key_exists($smtp->id, $form['smtp']->getItems()) ? $smtp->id : NULL,
            "email_info" => $group->email_info,
            "gid" => $group->id,
        ]);
        $this->template->nadpis = "Editace skupiny: " . $group->label;
        $this->template->linkBack = $this->link("Payment:detail", ["id" => $id]);
    }

    protected function createComponentGroupForm($name) : Form
    {
        $form = $this->prepareForm($this, $name);
        $form->addSelect("sisId");
        $form->addText("label", "Název")
            ->setAttribute("class", "form-control")
            ->addRule(Form::FILLED, "Musíte zadat název skupiny")
            ->setHtmlId("group-name-input");
        $form->addText("amount", "Výchozí částka")
            ->setAttribute("class", "form-control")
            ->addCondition(Form::FILLED)
            ->addRule(Form::FLOAT, "Částka musí být zadaná jako číslo");
        $form->addDatePicker("maturity", "Výchozí splatnost")
            ->setAttribute("class", "form-control");
        $form->addText("ks", "KS")
            ->setMaxLength(4)
            ->setAttribute("class", "form-control")
            ->addCondition(Form::FILLED)
            ->addRule(Form::INTEGER, "Konstantní symbol musí být číslo");
        $form->addSelect("smtp", "Odesílací email", $this->mail->getPairs($this->aid))
            ->setPrompt(\Model\MailService::EMAIL_SENDER);
        $form->addTextArea("email_info", "Informační email")
            ->setAttribute("class", "form-control")
            ->setDefaultValue($this->defaultEmails['base']['info']);
        $form->addHidden("type");
        $form->addHidden("gid");
        $form->addSubmit('send', "Založit skupinu")->setAttribute("class", "btn btn-primary");

        $form->onSubmit[] = function(Form $form) : void {
            $this->groupFormSubmitted($form);
        };
        return $form;
    }

    private function groupFormSubmitted(Form $form) : void
    {
        if (!$this->isEditable) {
            $this->flashMessage("Nemáte oprávnění pro změny skupin plateb", "danger");
            $this->redirect("default");
        }
        $v = $form->getValues();

        if ($v['maturity'] !== NULL && $v['maturity']->format("N") > 5) {
            $form['maturity']->addError("Splatnost nemůže být nastavena na víkend.");
            return;
        }

        //nastavení pro táborové skupiny
        if (isset($v->camp)) {
            $v->sisId = $v->camp;
        }

        if ($v->gid != "") {//EDIT
            $groupId = $v->gid;
            $isUpdate = $this->model->updateGroup($v->gid, [
                "label" => $v->label,
                "amount" => $v->amount != "" ? $v->amount : NULL,
                "maturity" => $v->maturity,
                "ks" => $v->ks != "" ? $v->ks : NULL,
                "email_info" => $v->email_info,
            ]);
            if ($v->smtp !== NULL) {
                $isUpdate = $this->mail->addSmtpGroup($groupId, $v->smtp) || $isUpdate;
            } else {
                $isUpdate = $this->mail->removeSmtpGroup($groupId) || $isUpdate;
            }
            if ($isUpdate) {
                $this->flashMessage("Skupina byla upravena");
            } else {
                $this->flashMessage("Skupina nebyla změněna!", "warning");
            }
            $this->redirect("Payment:detail", ["id" => $v->gid]);
        } else {//ADD
            $groupId = $this->model->createGroup(
                $this->aid,
                $v->type != "" ? $v->type : NULL,
                isset($v->sisId) ? (int)$v->sisId : NULL,
                $v->label,
                $v->maturity,
                $v->ks ? (int)$v->ks : NULL,
                isset($v->amount) ? (float)$v->amount : NULL,
                $v->email_info);
            if ($groupId) {
                if ($v->smtp !== NULL) {
                    $this->mail->addSmtpGroup($groupId, $v->smtp);
                }
                $this->flashMessage("Skupina byla založena");
                $this->redirect("Payment:detail", ["id" => $groupId]);
            } else {
                $this->flashMessage("Skupinu plateb se nepodařilo založit", "danger");
            }
        }
        $this->redirect("Default:");
    }

}
