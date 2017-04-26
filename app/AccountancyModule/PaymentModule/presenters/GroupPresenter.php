<?php

namespace App\AccountancyModule\PaymentModule;

use Model\Payment\Group\SkautisEntity;
use Model\Payment\Group\Type;
use Nette\Application\UI\Form;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class GroupPresenter extends BasePresenter
{

    /** @var \Model\PaymentService */
    protected $model;

    /** @var \Model\MailService */
    private $mail;

    /** @var \Model\EventService */
    private $camp;

    public function __construct(\Model\PaymentService $paymentService, \Model\MailService $mailService)
    {
        parent::__construct($paymentService);
        $this->model = $paymentService;
        $this->mail = $mailService;
    }

    protected function startup(): void
    {
        parent::startup();
        $this->camp = $this->context->getService("campService");
    }

    public function actionDefault($type = NULL): void
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

    public function renderEdit($id): void
    {
        $this->setView('default');

        if (!$this->isEditable) {
            $this->flashMessage("Nemáte oprávnění upravovat skupiny plateb", "danger");
            $this->redirect("Payment:default");
        }

        $form = $this['groupForm'];
        unset($form['sisId']);
        $form['send']->caption = "Upravit skupinu";

        $group = $this->model->getGroup($id);

        if ($group === NULL || $group->getUnitId() !== $this->aid) {
            $this->flashMessage("Skupina nebyla nalezena", "warning");
            $this->redirect("Payment:default");
        }

        $dto = $this->model->getGroup($id);
        $form->setDefaults([
            "label" => $dto->getName(),
            "amount" => $dto->getDefaultAmount(),
            "maturity" => $dto->getDueDate() ? $dto->getDueDate()->format(\DateTime::ISO8601) : NULL,
            "ks" => $dto->getConstantSymbol(),
            "nextVs" => $dto->getNextVariableSymbol(),
            "smtp" => $dto->getSmtpId(),
            "email_info" => $dto->getEmailTemplate(),
            "gid" => $id,
        ]);

        $existsPaymentWithVS = $this->model->getMaxVariableSymbol($dto->getId()) !== NULL;

        if($existsPaymentWithVS) {
            $form["nextVs"]->setDisabled(TRUE);
        }

        $this->template->nadpis = "Editace skupiny: " . $dto->getName();
        $this->template->linkBack = $this->link("Payment:detail", ["id" => $id]);
    }

    protected function createComponentGroupForm(): Form
    {
        $form = $this->formFactory->create();
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
        $form->addText("nextVs", "Další VS:")
            ->setMaxLength(10)
            ->addCondition(Form::FILLED)
            ->addRule(Form::INTEGER, "Variabilní symbol musí být číslo");
        $form->addSelect("smtp", "Email odesílatele", $this->mail->getPairs($this->aid))
            ->setPrompt("Vyberte email");
        $form->addTextArea("email_info", "Informační email")
            ->setAttribute("class", "form-control")
            ->setDefaultValue($this->getDefaultEmail('info'));
        $form->addHidden("type");
        $form->addHidden("gid");
        $form->addSubmit('send', "Založit skupinu")->setAttribute("class", "btn btn-primary");

        $form->onSubmit[] = function (Form $form): void {
            $this->groupFormSubmitted($form);
        };
        return $form;
    }

    private function groupFormSubmitted(Form $form): void
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
            $this->model->updateGroup(
                $groupId,
                $v->label,
                $v->amount ? (float)$v->amount : NULL,
                $v->maturity ? \DateTimeImmutable::createFromMutable($v->maturity) : NULL,
                $v->ks ? (int)$v->ks : NULL,
                $v->nextVs ? (int)$v->nextVs : NULL,
                $v->email_info,
                $v->smtp);

            $this->flashMessage('Skupina byla upravena');
        } else {//ADD
            $type = Type::isValidValue($v->type) ? Type::get($v->type) : NULL;

            $object = $type !== NULL ? new SkautisEntity((int)$v->sisId, $type) : NULL;

            $dueDate = $v->maturity !== NULL
                     ? \DateTimeImmutable::createFromMutable($v->maturity)
                     : NULL;

            $groupId = $this->model->createGroup(
                $this->aid,
                $object,
                $v->label,
                $dueDate,
                $v->ks ? (int)$v->ks : NULL,
                $v->nextVs ? (int)$v->nextVs : NULL,
                isset($v->amount) ? (float)$v->amount : NULL,
                $v->email_info,
                $v->smtp);

            $this->flashMessage('Skupina byla založena');
        }
        $this->redirect('Payment:detail', ['id' => $groupId]);
    }

    private function getDefaultEmail(string $name) : string
    {
        return file_get_contents(__DIR__.'/../templates/defaultEmails/'.$name.'.html');
    }

}
