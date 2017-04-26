<?php

namespace App\AccountancyModule\PaymentModule;

use Consistence\Enum\InvalidEnumValueException;
use Model\EventEntity;
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

    /** @var EventEntity */
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
            $this['groupForm']['skautisEntityId']->caption = "Tábor";
            $this['groupForm']['type']->setDefaultValue("camp");
            $this['groupForm']['skautisEntityId']
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
            unset($this['groupForm']['skautisEntityId']);
            $this['groupForm']->addHidden("skautisEntityId", $reg['ID']);
            $this['groupForm']->setDefaults([
                "label" => "Registrace " . $reg['Year'], $reg['Year'] . "-01-15",
            ]);
            $this->template->nadpis = "Založení skupiny plateb pro registraci";

        } else {//obecná skupina
            unset($this['groupForm']['skautisEntityId']);
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
        unset($form['skautisEntityId']);
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
            "dueDate" => $dto->getDueDate() ? $dto->getDueDate()->format(\DateTime::ISO8601) : NULL,
            "constantSymbol" => $dto->getConstantSymbol(),
            "nextVs" => $dto->getNextVariableSymbol(),
            "smtp" => $dto->getSmtpId(),
            "emailTemplate" => $dto->getEmailTemplate(),
            "groupId" => $id,
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
        $form->addSelect("skautisEntityId");
        $form->addText("label", "Název")
            ->setAttribute("class", "form-control")
            ->setRequired("Musíte zadat název skupiny")
            ->setHtmlId("group-name-input");
        $form->addText("amount", "Výchozí částka")
            ->setAttribute("class", "form-control")
            ->setRequired(FALSE)
            ->addRule(Form::FLOAT, "Částka musí být zadaná jako číslo");
        $form->addDatePicker("dueDate", "Výchozí splatnost")
            ->setAttribute("class", "form-control");
        $form->addText("constantSymbol", "KS")
            ->setMaxLength(4)
            ->setAttribute("class", "form-control")
            ->setRequired(FALSE)
            ->addRule(Form::INTEGER, "Konstantní symbol musí být číslo");
        $form->addText("nextVs", "Další VS:")
            ->setMaxLength(10)
            ->setRequired(FALSE)
            ->addRule(Form::INTEGER, "Variabilní symbol musí být číslo");
        $form->addSelect("smtp", "Email odesílatele", $this->mail->getPairs($this->aid))
            ->setPrompt("Vyberte email");
        $form->addTextArea("emailTemplate", "Informační email")
            ->setAttribute("class", "form-control")
            ->setDefaultValue($this->getDefaultEmail('info'));
        $form->addHidden("type");
        $form->addHidden("groupId");
        $form->addSubmit('send', "Založit skupinu")->setAttribute("class", "btn btn-primary");

        $form->onSuccess[] = function (Form $form): void {
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


        $dueDate = $v->dueDate !== NULL
            ? \DateTimeImmutable::createFromMutable($v->dueDate)
            : NULL;

        if ($dueDate !== NULL && $dueDate->format("N") > 5) {
            $form["dueDate"]->addError("Splatnost nemůže být nastavena na víkend.");
            return;
        }

        $groupId = $v->groupId !== "" ? (int)$v->groupId : NULL;
        $label = $v->label;
        $amount = $v->amount !== "" ? $v->amount : NULL;
        $constantSymbol = $v->constantSymbol !== "" ? $v->constantSymbol : NULL;
        $emailTemplate = $v->emailTemplate;
        $nextVs = $v->nextVs !== "" ? $v->nextVs : NULL;
        $smtpId = $v->smtp;

        if ($groupId !== NULL) {//EDIT
            $this->model->updateGroup(
                $groupId, $label, $amount, $dueDate, $constantSymbol, $nextVs, $emailTemplate, $smtpId
            );

            $this->flashMessage('Skupina byla upravena');
        } else {//ADD
            $entity = isset($v->skautisEntityId)
                    ? $this->createSkautisEntity($v->type, (int)$v->skautisEntityId)
                    : NULL;

            $groupId = $this->model->createGroup(
                $this->aid, $entity, $label, $dueDate, $constantSymbol, $nextVs, $amount, $emailTemplate, $smtpId
            );

            $this->flashMessage('Skupina byla založena');
        }
        $this->redirect('Payment:detail', ['id' => $groupId]);
    }

    private function createSkautisEntity(string $type, int $id): ?SkautisEntity
    {
        try {
            return new SkautisEntity($id, Type::get($type));
        } catch (InvalidEnumValueException $e) {
            return NULL;
        }
    }

    private function getDefaultEmail(string $name) : string
    {
        return file_get_contents(__DIR__.'/../templates/defaultEmails/'.$name.'.html');
    }

}
