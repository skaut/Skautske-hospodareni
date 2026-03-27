<?php

declare(strict_types=1);

namespace App\Components\Payment;

use App\Components\BaseControl;
use App\Model\Common\Services\QueryBus;
use App\Model\Common\UnitId;
use App\Model\DTO\Google\OAuth;
use App\Model\DTO\Payment\BankAccount;
use App\Model\DTO\Payment\GroupEmail;
use App\Model\Google\OAuthId;
use App\Model\Payment\EmailTemplate;
use App\Model\Payment\EmailType;
use App\Model\Payment\Group\PaymentDefaults;
use App\Model\Payment\Group\SkautisEntity;
use App\Model\Payment\PaymentService;
use App\Model\Payment\ReadModel\Queries\BankAccount\BankAccountsAccessibleByUnitsQuery;
use App\Model\Payment\ReadModel\Queries\GroupEmailQuery;
use App\Model\Payment\ReadModel\Queries\NextVariableSymbolSequenceQuery;
use App\Model\Payment\ReadModel\Queries\OAuthsAccessibleByGroupsQuery;
use App\Model\Unit\ReadModel\Queries\UnitsDetailQuery;
use App\Model\Unit\Unit;
use Assert\Assertion;
use Cake\Chronos\ChronosDate;
use Component\Forms\BaseForm;
use Component\Forms\DateControl;
use DateTimeImmutable;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\TextBase;
use Nette\Utils\ArrayHash;
use Nette\Utils\FileSystem;

use function array_filter;
use function array_map;
use function array_unique;
use function assert;

final class GroupForm extends BaseControl
{
    public function __construct(
        private UnitId $unitId,
        private ?SkautisEntity $skautisEntity,
        private ?int $groupId,
        private PaymentService $model,
        private QueryBus $queryBus,
    ) {
    }

    public function render(): void
    {
        $this->template->setFile(__DIR__.'/templates/GroupForm.latte');
        $this->template->render();
    }

    public function fillName(string $name): void
    {
        $nameControl = $this['form']['name'];

        assert($nameControl instanceof TextBase);

        $nameControl->setDefaultValue($name);
    }

    public function fillDueDate(ChronosDate $dueDate): void
    {
        if ($dueDate->isSaturday()) {
            $dueDate = $dueDate->addDays(2);
        } elseif ($dueDate->isSunday()) {
            $dueDate = $dueDate->addDays(1);
        }

        $dueDateControl = $this['form']['dueDate'];

        assert($dueDateControl instanceof DateControl);

        $dueDateControl->setDefaultValue($dueDate);
    }

    protected function createComponentForm(): BaseForm
    {
        $form = new BaseForm();
        $defaults = $this->buildDefaultsFromGroup();

        $form->addGroup('Základní údaje');
        $form->addText('name', 'Název')
            ->setHtmlAttribute('class', 'form-control')
            ->setRequired('Musíte zadat název skupiny')
            ->setHtmlId('group-name-input');

        $form->addText('amount', 'Výchozí částka')
            ->setHtmlAttribute('class', 'form-control')
            ->setRequired(false)
            ->setNullable()
            ->addRule(Form::FLOAT, 'Částka musí být zadaná jako číslo');

        $form->addDate('dueDate', 'Výchozí splatnost')
            ->disableWeekends()
            ->setHtmlAttribute('class', 'form-control')
            ->setRequired(false);

        $form->addText('constantSymbol', 'KS')
            ->setMaxLength(4)
            ->setHtmlAttribute('class', 'form-control')
            ->setRequired(false)
            ->setNullable()
            ->addRule(Form::INTEGER, 'Konstantní symbol musí být číslo');

        $nextVs = $this->queryBus->handle(
            new NextVariableSymbolSequenceQuery($this->unitId->toInt(), new DateTimeImmutable()),
        );

        $form->addVariableSymbol('nextVs', 'Další VS:')
            ->setDefaultValue($nextVs)
            ->setRequired(false)
            ->setDisabled($this->groupId !== null && $this->model->getMaxVariableSymbol($this->groupId) !== null)
            ->setNullable();

        $bankAccount = $form->addSelect('bankAccount', 'Bankovní účet', $this->bankAccountItems())
            ->setRequired(false)
            ->setPrompt('Vyberte bankovní účet');

        if ($this->groupId !== null) {
            $bankAccount->setHtmlAttribute('data-original-value', (string) ($defaults['bankAccount'] ?? ''));
            $bankAccount->setHtmlAttribute(
                'data-bank-account-change-message',
                'Opravdu chceš změnit bankovní účet této platební skupiny? Změna resetuje kurzor bankovního párování této skupiny. Historie již spárovaných plateb zůstane zachována.',
            );
        }

        $form->addSelect('oAuthId', 'E-mail odesílatele', $this->oAuthItems())
            ->setPrompt('Vyberte e-mail')
            ->setHtmlAttribute('class', 'ui--emailSelectbox'); // For acceptance testing

        $this->addEmailsToForm($form);

        $form->addSubmit('send', $this->groupId !== null ? 'Uložit skupinu' : 'Založit skupinu')
            ->setHtmlAttribute('class', 'btn btn-primary');

        $form->setDefaults($defaults);

        $form->onError[] = function (BaseForm $form): void {
            $this->formError($form);
        };

        $form->onSuccess[] = function (BaseForm $form): void {
            $this->formSucceeded($form);
        };

        return $form;
    }

    private function formError(BaseForm $form): void
    {
        foreach ($form->getErrors() as $error) {
            $this->flashMessage($error, 'danger');
        }
    }

    private function formSucceeded(BaseForm $form): void
    {
        $v = $form->getValues();

        $originalGroupData = $this->buildDefaultsFromGroup();

        $paymentDefaults = new PaymentDefaults(
            $v->amount,
            $v->dueDate === null ? null : new ChronosDate($v->dueDate),
            $v->constantSymbol,
            $v->nextVs ?? $originalGroupData['nextVs'] ?? null,
        );

        $emails = [
            EmailType::PAYMENT_INFO => $this->buildEmailTemplate($v, EmailType::PAYMENT_INFO),
            EmailType::PAYMENT_COMPLETED => $this->buildEmailTemplate($v, EmailType::PAYMENT_COMPLETED),
            EmailType::PAYMENT_REMINDER => $this->buildEmailTemplate($v, EmailType::PAYMENT_REMINDER),
        ];

        $emails = array_filter($emails);
        $oAuthId = OAuthId::fromStringOrNull($v->oAuthId);

        if ($this->groupId !== null) {// EDIT
            $this->model->updateGroup(
                $this->groupId,
                $v->name,
                $paymentDefaults,
                $emails,
                $oAuthId,
                $v->bankAccount,
                $v->emails[EmailType::PAYMENT_REMINDER]?->remindersEnabled ?? false,
            );

            $this->flashMessage('Skupina byla upravena');
        } else {// ADD
            $this->groupId = $this->model->createGroup(
                $this->unitId->toInt(),
                $this->skautisEntity,
                $v->name,
                $paymentDefaults,
                $emails,
                $oAuthId,
                $v->bankAccount,
                $v->emails[EmailType::PAYMENT_REMINDER]?->remindersEnabled ?? false,
            );

            $this->flashMessage('Skupina byla založena');
        }

        $this->getPresenter()->redirect(':Payments:Payment:default', ['id' => $this->groupId]);
    }

    private function getDefaultEmailBody(string $name): string
    {
        return FileSystem::read(__DIR__.'/../../Presentation/Payments/InvoiceSequenceList/defaultEmails/'.$name.'.html');
    }

    /** @return mixed[] */
    private function buildDefaultsFromGroup(): array
    {
        if ($this->groupId === null) {
            return [];
        }

        $group = $this->model->getGroup($this->groupId);

        Assertion::notNull($group);

        $emails = [];

        foreach (EmailType::getAvailableEnums() as $emailType) {
            $emails[$emailType->toString()] = $this->getEmailDefaults($this->groupId, $emailType);
            if ($emailType->toString() !== EmailType::PAYMENT_REMINDER) {
                continue;
            }

            $emails[$emailType->toString()]['remindersEnabled'] = $group->isRemindersEnabled();
        }

        return [
            'name' => $group->getName(),
            'amount' => $group->getDefaultAmount(),
            'dueDate' => $group->getDueDate(),
            'constantSymbol' => $group->getConstantSymbol(),
            'nextVs' => $group->getNextVariableSymbol(),
            'oAuthId' => $group->getOAuthId()?->toString(),
            'emails' => $emails,
            'groupId' => $this->groupId,
            'bankAccount' => $group->getBankAccountId(),
        ];
    }

    private function addEmailsToForm(BaseForm $form): void
    {
        $emailsContainer = $form->addContainer('emails');

        $emails = [
            EmailType::PAYMENT_INFO => 'E-mail s platebními údaji',
            EmailType::PAYMENT_COMPLETED => 'E-mail při dokončení platby',
            //  EmailType::PAYMENT_CANCELED =>  'E-mail při zrušení platby',
            EmailType::PAYMENT_REMINDER => 'E-mail upomínka platby',
        ];

        foreach ($emails as $type => $caption) {
            $group = $form->addGroup($caption, false);
            $container = $emailsContainer->addContainer($type);
            $container->setCurrentGroup($group);

            $subjectId = $type.'_subject';
            $bodyId = $type.'_body';
            $remindersId = $type.'_reminders';

            // Only payment info email is always saved
            if ($type !== EmailType::PAYMENT_INFO) {
                $container->addCheckbox('enabled', 'Aktivní')
                    ->setOption('class', 'form-check')
                    ->setHtmlAttribute('data-email-field', '1')
                    ->addCondition($form::FILLED)
                    ->toggle($subjectId)
                    ->toggle($bodyId)
                    ->toggle($remindersId);
            }

            if ($type === EmailType::PAYMENT_REMINDER) {
                $container->addCheckbox('remindersEnabled', 'Automaticky odeslat email po splatnosti')
                    ->setOption('id', $remindersId)
                    ->setHtmlAttribute('data-email-field', '1');
            }

            $container->addText('subject', 'Předmět e-mailu')
                ->setOption('id', $subjectId)
                ->setHtmlAttribute('data-email-field', '1');
            $container->addTextArea('body', 'Text mailu', 10, 20)
                ->setOption('id', $bodyId)
                ->setHtmlAttribute('class', 'form-control')
                ->setHtmlAttribute('data-email-field', '1')
                ->setDefaultValue($this->getDefaultEmailBody($type));
        }

        $form->setCurrentGroup();
    }

    private function buildEmailTemplate(ArrayHash $values, string $emailType): ?EmailTemplate
    {
        $emailValues = $values->emails->{$emailType};

        if ($emailType !== EmailType::PAYMENT_INFO && ! $emailValues->enabled) {
            return null;
        }

        return new EmailTemplate($emailValues->subject, $emailValues->body);
    }

    /** @return mixed[] */
    private function getEmailDefaults(int $groupId, EmailType $type): array
    {
        $email = $this->queryBus->handle(new GroupEmailQuery($groupId, $type));

        if ($email === null) {
            return [];
        }

        assert($email instanceof GroupEmail);

        return [
            'enabled' => $email->isEnabled(),
            'subject' => $email->getTemplate()->getSubject(),
            'body' => $email->getTemplate()->getBody(),
        ];
    }

    /** @return array<int, string> */
    private function bankAccountItems(): array
    {
        $bankAccounts = $this->queryBus->handle(new BankAccountsAccessibleByUnitsQuery($this->groupUnitIds()));

        $items = [];

        foreach ($bankAccounts as $bankAccount) {
            assert($bankAccount instanceof BankAccount);
            $items[$bankAccount->getId()] = $bankAccount->getName();
        }

        return $items;
    }

    /** @return array<string, array<string, string>> */
    private function oAuthItems(): array
    {
        $oAuths = $this->queryBus->handle(new OAuthsAccessibleByGroupsQuery($this->groupUnitIds()));

        $units = $this->queryBus->handle(
            new UnitsDetailQuery(
                array_unique(array_map(
                    function (OAuth $oAuth): int {
                        return $oAuth->getUnitId();
                    },
                    $oAuths,
                )),
            ),
        );

        $items = [];
        foreach ($oAuths as $oAuth) {
            assert($oAuth instanceof OAuth);

            $unit = $units[$oAuth->getUnitId()];
            assert($unit instanceof Unit);

            $items[$unit->getDisplayName()][$oAuth->getId()] = $oAuth->getEmail();
        }

        return $items;
    }

    /** @return int[] */
    private function groupUnitIds(): array
    {
        if ($this->groupId === null) {
            return [$this->unitId->toInt()]; // New group will be created with user's current unit
        }

        $group = $this->model->getGroup($this->groupId);
        assert($group !== null);

        return $group->getUnitIds();
    }
}
