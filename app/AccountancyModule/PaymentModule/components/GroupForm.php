<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule\Components;

use App\AccountancyModule\Components\BaseControl;
use App\AccountancyModule\Components\FormControls\DateControl;
use App\Forms\BaseForm;
use Assert\Assertion;
use Cake\Chronos\ChronosDate;
use DateTimeImmutable;
use Model\Common\Services\QueryBus;
use Model\Common\UnitId;
use Model\DTO\Google\OAuth;
use Model\DTO\Payment\BankAccount;
use Model\DTO\Payment\GroupEmail;
use Model\Google\OAuthId;
use Model\Payment\EmailTemplate;
use Model\Payment\EmailType;
use Model\Payment\Group\PaymentDefaults;
use Model\Payment\Group\SkautisEntity;
use Model\Payment\ReadModel\Queries\BankAccount\BankAccountsAccessibleByUnitsQuery;
use Model\Payment\ReadModel\Queries\GroupEmailQuery;
use Model\Payment\ReadModel\Queries\NextVariableSymbolSequenceQuery;
use Model\Payment\ReadModel\Queries\OAuthsAccessibleByGroupsQuery;
use Model\PaymentService;
use Model\Unit\ReadModel\Queries\UnitsDetailQuery;
use Model\Unit\Unit;
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
        private SkautisEntity|null $skautisEntity = null,
        private int|null $groupId = null,
        private PaymentService $model,
        private QueryBus $queryBus,
    ) {
    }

    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/templates/GroupForm.latte');
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

        $form->addSelect('bankAccount', 'Bankovní účet', $this->bankAccountItems())
            ->setRequired(false)
            ->setPrompt('Vyberte bankovní účet');

        $form->addSelect('oAuthId', 'E-mail odesílatele', $this->oAuthItems())
            ->setPrompt('Vyberte e-mail')
            ->setHtmlAttribute('class', 'ui--emailSelectbox'); // For acceptance testing

        $this->addEmailsToForm($form);

        $form->addSubmit('send', $this->groupId !== null ? 'Uložit skupinu' : 'Založit skupinu')
            ->setHtmlAttribute('class', 'btn btn-primary');

        $form->setDefaults($this->buildDefaultsFromGroup());

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
        ];

        $emails  = array_filter($emails);
        $oAuthId = OAuthId::fromStringOrNull($v->oAuthId);

        if ($this->groupId !== null) {//EDIT
            $this->model->updateGroup(
                $this->groupId,
                $v->name,
                $paymentDefaults,
                $emails,
                $oAuthId,
                $v->bankAccount,
            );

            $this->flashMessage('Skupina byla upravena');
        } else {//ADD
            $this->groupId = $this->model->createGroup(
                $this->unitId->toInt(),
                $this->skautisEntity,
                $v->name,
                $paymentDefaults,
                $emails,
                $oAuthId,
                $v->bankAccount,
            );

            $this->flashMessage('Skupina byla založena');
        }

        $this->getPresenter()->redirect(':Accountancy:Payment:Payment:default', ['id' => $this->groupId]);
    }

    private function getDefaultEmailBody(string $name): string
    {
        return FileSystem::read(__DIR__ . '/../templates/defaultEmails/' . $name . '.html');
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
        }

        return [
            'name' => $group->getName(),
            'amount' => $group->getDefaultAmount(),
            'dueDate' => $group->getDueDate()->toNative(),
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

            $container->addText('subject', 'Předmět e-mailu')
                ->setOption('id', $subjectId);
            $container->addTextArea('body', 'Text mailu')
                ->setOption('id', $bodyId)
                ->setHtmlAttribute('class', 'form-control')
                ->setDefaultValue($this->getDefaultEmailBody($type));
        }

        $form->setCurrentGroup();
    }

    private function buildEmailTemplate(ArrayHash $values, string $emailType): EmailTemplate|null
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
