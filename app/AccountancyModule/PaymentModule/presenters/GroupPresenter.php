<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule;

use App\Forms\BaseForm;
use Cake\Chronos\Date;
use Consistence\Enum\InvalidEnumValueException;
use Model\DTO\Payment\GroupEmail;
use Model\EventEntity;
use Model\MailService;
use Model\Payment\BankAccountService;
use Model\Payment\DueDateIsNotWorkday;
use Model\Payment\EmailTemplate;
use Model\Payment\EmailType;
use Model\Payment\Group\PaymentDefaults;
use Model\Payment\Group\SkautisEntity;
use Model\Payment\Group\Type;
use Model\Payment\ReadModel\Queries\GroupEmailQuery;
use Model\Payment\ReadModel\Queries\NextVariableSymbolSequenceQuery;
use Model\PaymentService;
use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;
use function array_diff_key;
use function array_filter;
use function date;
use function file_get_contents;
use function in_array;

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
    ) {
        parent::__construct();
        $this->model        = $model;
        $this->mail         = $mailService;
        $this->camp         = $camp;
        $this->bankAccounts = $bankAccounts;
    }

    public function actionDefault(?string $type = null) : void
    {
        if (! $this->isEditable) {
            $this->flashMessage('Nemáte oprávnění upravovat skupiny plateb', 'danger');
            $this->redirect('Payment:default');
        }

        if ($type === 'camp') {
            $allCamps = $this->camp->getEvent()->getAll(date('Y'));
            $camps    = [];
            foreach (array_diff_key($allCamps, $this->model->getCampIds()) as $id => $c) {
                $camps[$id] = $c['DisplayName'];
            }
            $this['groupForm']['skautisEntityId']->caption = 'Tábor';
            $this['groupForm']['type']->setDefaultValue('camp');
            $this['groupForm']['skautisEntityId']
                ->addRule(Form::FILLED, 'Vyberte tábor kterého se skupina týká!')
                ->setPrompt('Vyberte tábor')
                ->setHtmlId('camp-select')
                ->setItems($camps);
            $header = 'Založení skupiny plateb tábora';
        } elseif ($type === 'registration') {
            $reg = $this->model->getNewestRegistration();
            if ($reg === []) {
                $this->flashMessage('Nemáte založenou žádnou otevřenou registraci', 'warning');
                $this->redirect('Payment:default');
            }
            $this['groupForm']['type']->setDefaultValue('registration');
            unset($this['groupForm']['amount']);
            unset($this['groupForm']['skautisEntityId']);
            $this['groupForm']->addHidden('skautisEntityId', $reg['ID']);
            $this['groupForm']->setDefaults(
                [
                    'label' => 'Registrace ' . $reg['Year'],
                    'dueDate' => new Date($reg['Year'] . '-01-15'),
                ]
            );
            $header = 'Založení skupiny plateb pro registraci';
        } else {//obecná skupina
            unset($this['groupForm']['skautisEntityId']);
            $header = 'Založení skupiny plateb';
        }

        $defaultNextVs = $this->queryBus->handle(
            new NextVariableSymbolSequenceQuery($this->getCurrentUnitId(), new \DateTimeImmutable())
        );
        $this['groupForm']['nextVs']->setDefaultValue($defaultNextVs);

        $this->template->setParameters([
            'nadpis' => $header,
            'linkBack' => $this->link('Default:'),
        ]);
    }

    public function renderEdit(int $id) : void
    {
        $this->setView('default');

        if (! $this->isEditable) {
            $this->flashMessage('Nemáte oprávnění upravovat skupiny plateb', 'danger');
            $this->redirect('Payment:default');
        }

        $form = $this['groupForm'];
        unset($form['skautisEntityId']);
        $form['send']->caption = 'Upravit skupinu';

        $group = $this->model->getGroup($id);

        if ($group === null || ! in_array($this->getCurrentUnitId(), $group->getUnitIds(), true)) {
            $this->flashMessage('Skupina nebyla nalezena', 'warning');
            $this->redirect('Payment:default');
        }

        $form->setDefaults(
            [
            'label' => $group->getName(),
            'amount' => $group->getDefaultAmount(),
            'dueDate' => $group->getDueDate(),
            'constantSymbol' => $group->getConstantSymbol(),
            'nextVs' => $group->getNextVariableSymbol(),
            'smtp' => $group->getSmtpId(),
            'emails' => [
                EmailType::PAYMENT_INFO => $this->getEmailDefaults($id, EmailType::get(EmailType::PAYMENT_INFO)),
                EmailType::PAYMENT_COMPLETED => $this->getEmailDefaults($id, EmailType::get(EmailType::PAYMENT_COMPLETED)),
            ],
            'groupId' => $id,
            'bankAccount' => $group->getBankAccountId(),
            ]
        );

        $existsPaymentWithVS = $this->model->getMaxVariableSymbol($group->getId()) !== null;

        if ($existsPaymentWithVS) {
            $form['nextVs']->setDisabled(true);
        }

        $this->template->setParameters([
            'nadpis'   => 'Editace skupiny: ' . $group->getName(),
            'linkBack' => $this->link('Payment:detail', ['id' => $id]),
        ]);
    }

    protected function createComponentGroupForm() : Form
    {
        $form = new BaseForm();
        $form->useBootstrap4();

        $unitId = $this->getCurrentUnitId();

        $form->addGroup('Základní údaje');
        $form->addSelect('skautisEntityId');
        $form->addText('label', 'Název')
            ->setAttribute('class', 'form-control')
            ->setRequired('Musíte zadat název skupiny')
            ->setHtmlId('group-name-input');
        $form->addText('amount', 'Výchozí částka')
            ->setAttribute('class', 'form-control')
            ->setRequired(false)
            ->addRule(Form::FLOAT, 'Částka musí být zadaná jako číslo');
        $form->addDate('dueDate', 'Výchozí splatnost')
            ->setAttribute('class', 'form-control');
        $form->addText('constantSymbol', 'KS')
            ->setMaxLength(4)
            ->setAttribute('class', 'form-control')
            ->setRequired(false)
            ->addRule(Form::INTEGER, 'Konstantní symbol musí být číslo');
        $form->addVariableSymbol('nextVs', 'Další VS:')
            ->setRequired(false);

        $bankAccounts     = $this->bankAccounts->findByUnit($unitId);
        $bankAccountItems = [];
        foreach ($bankAccounts as $bankAccount) {
            $bankAccountItems[$bankAccount->getId()] = $bankAccount->getName();
        }

        $form->addSelect('bankAccount', 'Bankovní účet', $bankAccountItems)
            ->setPrompt('Vyberte bankovní účet');
        $form->addSelect('smtp', 'Email odesílatele', $this->mail->getPairs($unitId))
            ->setPrompt('Vyberte email')
            ->setAttribute('class', 'ui--emailSelectbox'); // For acceptance testing

        $this->addEmailsToForm($form);

        $form->addHidden('type');
        $form->addHidden('groupId');
        $form->addSubmit('send', 'Založit skupinu')->setAttribute('class', 'btn btn-primary');

        $form->onSuccess[] = function (Form $form) : void {
            $this->groupFormSubmitted($form);
        };
        return $form;
    }

    private function groupFormSubmitted(Form $form) : void
    {
        if (! $this->isEditable) {
            $this->flashMessage('Nemáte oprávnění pro změny skupin plateb', 'danger');
            $this->redirect('default');
        }
        $v = $form->getValues();

        $name = $v->label;
        try {
            $paymentDefaults = $this->buildPaymentDefaults($v);
        } catch (DueDateIsNotWorkday $e) {
            $form['dueDate']->addError('Splatnost nemůže být nastavena na víkend.');
            return;
        }
        $smtpId = $v->smtp;

        $emails = [
            EmailType::PAYMENT_INFO => $this->buildEmailTemplate($v, EmailType::PAYMENT_INFO),
            EmailType::PAYMENT_COMPLETED => $this->buildEmailTemplate($v, EmailType::PAYMENT_COMPLETED),
        ];

        /**
         * @var EmailTemplate[] $emails
         */
        $emails = array_filter(
            $emails,
            function (?EmailTemplate $email) : bool {
                return $email !== null; // Remove not submitted emails
            }
        );

        $groupId = $v->groupId !== '' ? (int) $v->groupId : null;

        if ($groupId !== null) {//EDIT
            $this->model->updateGroup($groupId, $name, $paymentDefaults, $emails, $smtpId, $v->bankAccount);

            $this->flashMessage('Skupina byla upravena');
        } else {//ADD
            $entity = isset($v->skautisEntityId)
                ? $this->createSkautisEntity($v->type, (int) $v->skautisEntityId)
                : null;

            $unitId = $this->getCurrentUnitId();

            $groupId = $this->model->createGroup($unitId, $entity, $name, $paymentDefaults, $emails, $smtpId, $v->bankAccount);

            $this->flashMessage('Skupina byla založena');
        }
        $this->redirect('Payment:detail', ['id' => $groupId]);
    }

    private function createSkautisEntity(string $type, int $id) : ?SkautisEntity
    {
        try {
            return new SkautisEntity($id, Type::get($type));
        } catch (InvalidEnumValueException $e) {
            return null;
        }
    }

    /**
     * @throws DueDateIsNotWorkday
     */
    private function buildPaymentDefaults(ArrayHash $values) : PaymentDefaults
    {
        $amount         = (isset($values->amount) && $values->amount !== '') ? $values->amount : null;
        $constantSymbol = $values->constantSymbol !== '' ? $values->constantSymbol : null;

        return new PaymentDefaults($amount, $values->dueDate, $constantSymbol, $values->nextVs);
    }

    private function getDefaultEmail(string $name) : string
    {
        return file_get_contents(__DIR__ . '/../templates/defaultEmails/' . $name . '.html');
    }

    private function addEmailsToForm(BaseForm $form) : void
    {
        $emailsContainer = $form->addContainer('emails');

        $emails = [
            EmailType::PAYMENT_INFO => 'Email s platebními údaji',
            EmailType::PAYMENT_COMPLETED => 'Email při dokončení platby',
        ];

        foreach ($emails as $type => $caption) {
            $group     = $form->addGroup($caption, false);
            $container = $emailsContainer->addContainer($type);
            $container->setCurrentGroup($group);

            $subjectId = $type . '_subject';
            $bodyId    = $type . '_body';

            // Only payment info email is always saved
            if ($type !== EmailType::PAYMENT_INFO) {
                $container->addCheckbox('enabled', 'Aktivní')
                    ->setOption('class', 'form-check')
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

    private function buildEmailTemplate(ArrayHash $values, string $emailType) : ?EmailTemplate
    {
        $emailValues = $values->emails->{$emailType};

        if ($emailType !== EmailType::PAYMENT_INFO && ! $emailValues->enabled) {
            return null;
        }

        return new EmailTemplate($emailValues->subject, $emailValues->body);
    }

    /**
     * @return mixed[]
     */
    private function getEmailDefaults(int $groupId, EmailType $type) : array
    {
        /**
         * @var GroupEmail|NULL $email
         */
        $email = $this->queryBus->handle(new GroupEmailQuery($groupId, $type));

        if ($email === null) {
            return [];
        }

        return [
            'enabled' => $email->isEnabled(),
            'subject' => $email->getTemplate()->getSubject(),
            'body' => $email->getTemplate()->getBody(),
        ];
    }
}
