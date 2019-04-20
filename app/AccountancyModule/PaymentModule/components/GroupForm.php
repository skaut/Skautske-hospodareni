<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule\Components;

use App\AccountancyModule\Components\BaseControl;
use App\AccountancyModule\Components\FormControls\DateControl;
use App\Forms\BaseForm;
use Assert\Assertion;
use Cake\Chronos\Date;
use eGen\MessageBus\Bus\QueryBus;
use Model\Common\UnitId;
use Model\DTO\Payment\GroupEmail;
use Model\MailService;
use Model\Payment\BankAccountService;
use Model\Payment\EmailTemplate;
use Model\Payment\EmailType;
use Model\Payment\Group\PaymentDefaults;
use Model\Payment\Group\SkautisEntity;
use Model\Payment\ReadModel\Queries\GroupEmailQuery;
use Model\Payment\ReadModel\Queries\NextVariableSymbolSequenceQuery;
use Model\PaymentService;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\TextBase;
use Nette\Utils\ArrayHash;
use function array_filter;
use function assert;
use function file_get_contents;

final class GroupForm extends BaseControl
{
    /** @var UnitId */
    private $unitId;

    /** @var SkautisEntity|null */
    private $skautisEntity;

    /** @var int|null */
    private $groupId;

    /** @var BankAccountService */
    private $bankAccounts;

    /** @var PaymentService */
    private $model;

    /** @var MailService */
    private $mail;

    /** @var QueryBus */
    private $queryBus;

    public function __construct(
        UnitId $unitId,
        ?SkautisEntity $skautisEntity,
        ?int $groupId,
        BankAccountService $bankAccounts,
        PaymentService $model,
        MailService $mail,
        QueryBus $queryBus
    ) {
        parent::__construct();
        $this->unitId        = $unitId;
        $this->skautisEntity = $skautisEntity;
        $this->groupId       = $groupId;
        $this->bankAccounts  = $bankAccounts;
        $this->model         = $model;
        $this->mail          = $mail;
        $this->queryBus      = $queryBus;
    }

    public function render() : void
    {
        $this->template->setFile(__DIR__ . '/templates/GroupForm.latte');
        $this->template->render();
    }

    public function fillName(string $name) : void
    {
        $nameControl = $this['form']['name'];

        assert($nameControl instanceof TextBase);

        $nameControl->setDefaultValue($name);
    }

    public function fillDueDate(Date $dueDate) : void
    {
        $dueDateControl = $this['form']['dueDate'];

        assert($dueDateControl instanceof DateControl);

        $dueDateControl->setDefaultValue($dueDate);
    }

    protected function createComponentForm() : BaseForm
    {
        $form = new BaseForm();
        $form->useBootstrap4();

        $unitId = $this->unitId->toInt();

        $form->addGroup('Základní údaje');
        $form->addText('name', 'Název')
            ->setAttribute('class', 'form-control')
            ->setRequired('Musíte zadat název skupiny')
            ->setHtmlId('group-name-input');

        $form->addText('amount', 'Výchozí částka')
            ->setAttribute('class', 'form-control')
            ->setRequired(false)
            ->setNullable()
            ->addRule(Form::FLOAT, 'Částka musí být zadaná jako číslo');

        $form->addDate('dueDate', 'Výchozí splatnost')
            ->setAttribute('class', 'form-control')
            ->setRequired(false)
            ->setNullable()
            ->addRule(function (DateControl $control) : bool {
                $value = $control->getValue();
                return $value === null || $value->isWeekday();
            });

        $form->addText('constantSymbol', 'KS')
            ->setMaxLength(4)
            ->setAttribute('class', 'form-control')
            ->setRequired(false)
            ->setNullable()
            ->addRule(Form::INTEGER, 'Konstantní symbol musí být číslo');

        $nextVs = $this->queryBus->handle(
            new NextVariableSymbolSequenceQuery($this->unitId->toInt(), new \DateTimeImmutable())
        );

        $form->addVariableSymbol('nextVs', 'Další VS:')
            ->setDefaultValue($nextVs)
            ->setRequired(false)
            ->setDisabled($this->groupId !== null && $this->model->getMaxVariableSymbol($this->groupId) !== null)
            ->setNullable();

        $bankAccounts     = $this->bankAccounts->findByUnit($unitId);
        $bankAccountItems = [];
        foreach ($bankAccounts as $bankAccount) {
            $bankAccountItems[$bankAccount->getId()] = $bankAccount->getName();
        }

        $form->addSelect('bankAccount', 'Bankovní účet', $bankAccountItems)
            ->setRequired(false)
            ->setPrompt('Vyberte bankovní účet');

        $form->addSelect('smtp', 'Email odesílatele', $this->mail->getPairs($unitId))
            ->setPrompt('Vyberte email')
            ->setAttribute('class', 'ui--emailSelectbox'); // For acceptance testing

        $this->addEmailsToForm($form);

        $form->addSubmit('send', $this->groupId !== null ? 'Uložit skupinu' : 'Založit skupinu')
            ->setAttribute('class', 'btn btn-primary');

        if ($this->groupId !== null) {
            $form->setDefaults($this->buildDefaultsFromGroup());
        }

        $form->onSuccess[] = function (BaseForm $form) : void {
            $this->formSucceeded($form);
        };

        return $form;
    }

    private function formSucceeded(BaseForm $form) : void
    {
        $v = $form->getValues();

        $name            = $v->name;
        $paymentDefaults = new PaymentDefaults($v->amount, $v->dueDate, $v->constantSymbol, $v->nextVs);
        $smtpId          = $v->smtp;

        $emails = [
            EmailType::PAYMENT_INFO => $this->buildEmailTemplate($v, EmailType::PAYMENT_INFO),
            EmailType::PAYMENT_COMPLETED => $this->buildEmailTemplate($v, EmailType::PAYMENT_COMPLETED),
        ];

        /** @var EmailTemplate[] $emails */
        $emails = array_filter($emails);

        if ($this->groupId !== null) {//EDIT
            $this->model->updateGroup($this->groupId, $name, $paymentDefaults, $emails, $smtpId, $v->bankAccount);

            $this->flashMessage('Skupina byla upravena');
        } else {//ADD
            $this->groupId = $this->model->createGroup(
                $this->unitId->toInt(),
                $this->skautisEntity,
                $name,
                $paymentDefaults,
                $emails,
                $smtpId,
                $v->bankAccount
            );

            $this->flashMessage('Skupina byla založena');
        }
        $this->getPresenter()->redirect('Payment:detail', ['id' => $this->groupId]);
    }


    private function getDefaultEmailBody(string $name) : string
    {
        return file_get_contents(__DIR__ . '/../templates/defaultEmails/' . $name . '.html');
    }

    /**
     * @return mixed[]
     */
    private function buildDefaultsFromGroup() : array
    {
        $group = $this->model->getGroup($this->groupId);

        Assertion::notNull($group);

        $emails = [];

        foreach (EmailType::getAvailableEnums() as $emailType) {
            $emails[$emailType->toString()] = $this->getEmailDefaults($this->groupId, $emailType);
        }

        return [
            'name' => $group->getName(),
            'amount' => $group->getDefaultAmount(),
            'dueDate' => $group->getDueDate(),
            'constantSymbol' => $group->getConstantSymbol(),
            'nextVs' => $group->getNextVariableSymbol(),
            'smtp' => $group->getSmtpId(),
            'emails' => $emails,
            'groupId' => $this->groupId,
            'bankAccount' => $group->getBankAccountId(),
        ];
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
                ->setDefaultValue($this->getDefaultEmailBody($type));
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
