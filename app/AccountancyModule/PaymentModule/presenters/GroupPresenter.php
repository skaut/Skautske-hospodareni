<?php

namespace App\AccountancyModule\PaymentModule;

use App\Forms\BaseForm;
use Consistence\Enum\InvalidEnumValueException;
use Consistence\Type\ArrayType\ArrayType;
use Model\DTO\Payment\BankAccount;
use Model\DTO\Payment\GroupEmail;
use Model\EventEntity;
use Model\MailService;
use Model\Payment\BankAccountService;
use Model\Payment\DueDateIsNotWorkdayException;
use Model\Payment\EmailTemplate;
use Model\Payment\EmailType;
use Model\Payment\Group\PaymentDefaults;
use Model\Payment\Group\SkautisEntity;
use Model\Payment\Group\Type;
use Model\Payment\ReadModel\Queries\GroupEmailQuery;
use Model\Payment\ReadModel\Queries\NextVariableSymbolSequenceQuery;
use Model\Payment\VariableSymbol;
use Model\PaymentService;
use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class GroupPresenter extends BasePresenter
{

    /** @var PaymentService */
    private $model;

    /** @var MailService */
    private $mail;

    /** @var EventEntity */
    private $camp;

    /** @var BankAccountService */
    private $bankAccounts;

    public function __construct(
        BankAccountService $bankAccounts,
        PaymentService $model,
        MailService $mailService,
        EventEntity $camp
    )
    {
        parent::__construct();
        $this->model = $model;
        $this->mail = $mailService;
        $this->camp = $camp;
        $this->bankAccounts = $bankAccounts;
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

        $defaultNextVs = $this->queryBus->handle(
            new NextVariableSymbolSequenceQuery($this->getCurrentUnitId(), new \DateTimeImmutable())
        );
        $this['groupForm']['nextVs']->setDefaultValue($defaultNextVs);

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

        if ($group === NULL || $group->getUnitId() !== $this->getCurrentUnitId()) {
            $this->flashMessage("Skupina nebyla nalezena", "warning");
            $this->redirect("Payment:default");
        }

        $form->setDefaults([
            "label" => $group->getName(),
            "amount" => $group->getDefaultAmount(),
            "dueDate" => $group->getDueDate() ? $group->getDueDate()->format(\DateTime::ISO8601) : NULL,
            "constantSymbol" => $group->getConstantSymbol(),
            "nextVs" => $group->getNextVariableSymbol(),
            "smtp" => $group->getSmtpId(),
            'emails' => [
                EmailType::PAYMENT_INFO => $this->getEmailDefaults($id, EmailType::get(EmailType::PAYMENT_INFO)),
                EmailType::PAYMENT_COMPLETED => $this->getEmailDefaults($id, EmailType::get(EmailType::PAYMENT_COMPLETED)),
            ],
            "groupId" => $id,
            'bankAccount' => $group->getBankAccountId(),
        ]);

        $existsPaymentWithVS = $this->model->getMaxVariableSymbol($group->getId()) !== NULL;

        if($existsPaymentWithVS) {
            $form["nextVs"]->setDisabled(TRUE);
        }

        $this->template->nadpis = "Editace skupiny: " . $group->getName();
        $this->template->linkBack = $this->link("Payment:detail", ["id" => $id]);
    }

    protected function createComponentGroupForm(): Form
    {
        $form = new BaseForm();

        $unitId = $this->getCurrentUnitId();

        $form->addGroup('Základní údaje');
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
        $form->addVariableSymbol("nextVs", "Další VS:")
            ->setRequired(FALSE);

        $bankAccounts = $this->bankAccounts->findByUnit($unitId);
        $bankAccountItems = [];
        foreach($bankAccounts as $bankAccount) {
            $bankAccountItems[$bankAccount->getId()] = $bankAccount->getName();
        }

        $form->addSelect('bankAccount', 'Bankovní účet', $bankAccountItems)
            ->setPrompt('Vyberte bankovní účet');
        $form->addSelect("smtp", "Email odesílatele", $this->mail->getPairs($unitId))
            ->setPrompt("Vyberte email")
            ->setAttribute('class', 'ui--emailSelectbox'); // For acceptance testing

        $this->addEmailsToForm($form);

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

        $name = $v->label;
        try {
            $paymentDefaults = $this->buildPaymentDefaults($v);
        } catch (DueDateIsNotWorkdayException $e) {
            $form["dueDate"]->addError("Splatnost nemůže být nastavena na víkend.");
            return;
        }
        $smtpId = $v->smtp;

        $emails = [
            EmailType::PAYMENT_INFO => $this->buildEmailTemplate($v, EmailType::PAYMENT_INFO),
            EmailType::PAYMENT_COMPLETED => $this->buildEmailTemplate($v, EmailType::PAYMENT_COMPLETED),
        ];

        /** @var array<string, EmailTemplate> $emails */
        $emails = array_filter($emails, function (?EmailTemplate $email): bool {
            return $email !== NULL; // Remove not submitted emails
        });

        $groupId = $v->groupId !== "" ? (int)$v->groupId : NULL;

        if ($groupId !== NULL) {//EDIT
            $this->model->updateGroup($groupId, $name, $paymentDefaults, $emails, $smtpId, $v->bankAccount);

            $this->flashMessage('Skupina byla upravena');
        } else {//ADD
            $entity = isset($v->skautisEntityId)
                    ? $this->createSkautisEntity($v->type, (int)$v->skautisEntityId)
                    : NULL;

            $unitId = $this->getCurrentUnitId();

            $groupId = $this->model->createGroup($unitId, $entity, $name, $paymentDefaults, $emails, $smtpId, $v->bankAccount);

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

    /**
     * @throws DueDateIsNotWorkdayException
     */
    private function buildPaymentDefaults(ArrayHash $values): PaymentDefaults
    {
        $amount = (isset($values->amount) && $values->amount !== "") ? $values->amount : NULL;
        $constantSymbol = $values->constantSymbol !== "" ? $values->constantSymbol : NULL;
        $dueDate = $values->dueDate !== NULL
            ? \DateTimeImmutable::createFromMutable($values->dueDate)
            : NULL;

        return new PaymentDefaults($amount, $dueDate, $constantSymbol, $values->nextVs);
    }

    private function getDefaultEmail(string $name) : string
    {
        return file_get_contents(__DIR__.'/../templates/defaultEmails/'.$name.'.html');
    }

    private function addEmailsToForm(BaseForm $form): void
    {
        $emailsContainer = $form->addContainer('emails');

        $emails = [
            EmailType::PAYMENT_INFO => 'Email s platebními údaji',
            EmailType::PAYMENT_COMPLETED => 'Email při dokončení platby',
        ];

        foreach ($emails as $type => $caption) {
            $group = $form->addGroup($caption, FALSE);
            $container = $emailsContainer->addContainer($type);
            $container->setCurrentGroup($group);

            $subjectId = $type . '_subject';
            $bodyId = $type . '_body';

            // Only payment info email is always saved
            if ($type !== EmailType::PAYMENT_INFO) {
                $container->addCheckbox('enabled', 'Aktivní')
                    ->addCondition($form::FILLED)
                    ->toggle($subjectId)
                    ->toggle($bodyId);
            }

            $container->addText('subject', 'Předmět emailu')
                ->setOption('id', $subjectId);
            $container->addTextArea('body', 'Text mailu')
                ->setOption('id', $bodyId)
                ->setAttribute('class', 'form-control')
                ->setDefaultValue($this->getDefaultEmail($type));

        }

        $form->setCurrentGroup();
    }

    private function buildEmailTemplate(ArrayHash $values, string $emailType): ?EmailTemplate
    {
        $emailValues = $values->emails->{$emailType};

        if ($emailType !== EmailType::PAYMENT_INFO && ! $emailValues->enabled) {
            return NULL;
        }

        return new EmailTemplate($emailValues->subject, $emailValues->body);
    }

    private function getEmailDefaults(int $groupId, EmailType $type): array
    {
        /** @var GroupEmail|NULL $email */
        $email = $this->queryBus->handle(new GroupEmailQuery($groupId, $type));

        if ($email === NULL) {
            return [];
        }

        return [
            'enabled' => $email->isEnabled(),
            'subject' => $email->getTemplate()->getSubject(),
            'body' => $email->getTemplate()->getBody(),
        ];
    }

}
