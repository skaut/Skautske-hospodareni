<?php

declare(strict_types=1);

namespace App\Components\Payment;

use App\Components\BaseControl;
use App\Model\Bank\BankService;
use App\Model\Bank\Enum\BankTransactionSource;
use App\Model\Common\Services\QueryBus;
use App\Model\Common\UnitId;
use App\Model\DTO\Google\OAuth;
use App\Model\DTO\Payment\BankAccount;
use App\Model\DTO\Payment\GroupEmail;
use App\Model\Google\OAuthId;
use App\Model\Payment\EmailTemplate;
use App\Model\Payment\EmailType;
use App\Model\Payment\Group;
use App\Model\Payment\Group\PaymentDefaults;
use App\Model\Payment\Group\SkautisEntity;
use App\Model\Payment\PaymentService;
use App\Model\Payment\ReadModel\Queries\BankAccount\BankAccountsAccessibleByUnitsQuery;
use App\Model\Payment\ReadModel\Queries\GroupEmailQuery;
use App\Model\Payment\ReadModel\Queries\NextVariableSymbolSequenceQuery;
use App\Model\Payment\ReadModel\Queries\OAuthsAccessibleByGroupsQuery;
use App\Model\Unit\ReadModel\Queries\UnitsDetailQuery;
use App\Model\Unit\Unit;
use App\Model\Utils\MoneyFactory;
use Assert\Assertion;
use Cake\Chronos\ChronosDate;
use Component\Forms\BaseForm;
use Component\Forms\DateControl;
use DateTimeImmutable;
use LogicException;
use Nette\Application\UI\Form;
use Nette\Forms\Container;
use Nette\Forms\Controls\Checkbox;
use Nette\Forms\Controls\SelectBox;
use Nette\Forms\Controls\TextBase;
use Nette\Utils\ArrayHash;
use Nette\Utils\FileSystem;
use Nette\Utils\Json;

use function array_filter;
use function array_key_exists;
use function array_map;
use function array_unique;
use function array_values;
use function in_array;
use function is_numeric;

final class GroupForm extends BaseControl
{
    private const FIO_ONLY_PAIRING_MESSAGE = 'Automatické párování je dostupné pouze pro účet napojený na FIO. U účtu s importem GPC se páruje jen ručně.';

    /** @var list<BankAccount>|null */
    private ?array $accessibleBankAccounts = null;

    public function __construct(
        private UnitId $unitId,
        private ?SkautisEntity $skautisEntity,
        private ?int $groupId,
        private ?int $cloneSourceGroupId,
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

        if (! $nameControl instanceof TextBase) {
            throw new LogicException('Assertion failed.');
        }
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

        if (! $dueDateControl instanceof DateControl) {
            throw new LogicException('Assertion failed.');
        }
        $dueDateControl->setDefaultValue($dueDate);
    }

    protected function createComponentForm(): BaseForm
    {
        $form = new BaseForm();
        $form->getElementPrototype()->addClass('inline-errors');
        $bankAccountItems = $this->bankAccountItems();
        $oAuthItems = $this->oAuthItems();
        $defaults = $this->buildDefaultsFromGroup($bankAccountItems, $oAuthItems);

        $form->addGroup('Základní údaje');
        $form->addText('name', 'Název')
            ->setHtmlAttribute('class', 'form-control')
            ->setRequired('Musíte zadat název skupiny')
            ->setHtmlId('group-name-input');

        $form->addText('amount', 'Výchozí částka')
            ->setHtmlAttribute('class', 'form-control')
            ->setRequired(false)
            ->setNullable()
            ->addRule(Form::FLOAT, 'Částka musí být zadaná jako číslo')
            ->addRule(Form::PATTERN, PaymentFormFields::MONEY_PATTERN_MESSAGE, PaymentFormFields::MONEY_PATTERN);

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

        $bankAccount = $form->addSelect('bankAccount', 'Bankovní účet', $bankAccountItems)
            ->setRequired(false)
            ->setPrompt('Vyberte bankovní účet');

        $bankAccount->setHtmlAttribute('data-gpc-bank-accounts', Json::encode($this->gpcBankAccountIds()));

        if ($this->groupId !== null) {
            $bankAccount->setHtmlAttribute('data-original-value', (string) ($defaults['bankAccount'] ?? ''));
            $bankAccount->setHtmlAttribute(
                'data-bank-account-change-message',
                'Opravdu chceš změnit bankovní účet této platební skupiny? Změna resetuje kurzor bankovního párování této skupiny. Historie již spárovaných plateb zůstane zachována.',
            );
        }

        $pairingGroup = $form->addGroup('Bankovní párování', false);
        $form->setCurrentGroup($pairingGroup);
        $form->addCheckbox('automaticPairingEnabled', 'Automaticky párovat úhrady v cronu')
            ->setOption('description', 'Cron páruje jen skupiny, které mají tuto volbu výslovně zapnutou.')
            ->setHtmlAttribute('data-bank-pairing-field', '1')
            ->setHtmlAttribute('data-fio-only-message', self::FIO_ONLY_PAIRING_MESSAGE);
        $form->addText('pairingDaysBack', 'Rozšířit hledání zpětně o dnů')
            ->setDefaultValue((string) BankService::DAYS_BACK_DEFAULT)
            ->setRequired(false)
            ->setNullable()
            ->addRule(Form::INTEGER, 'Počet dnů musí být celé číslo.')
            ->addRule(Form::MIN, 'Počet dnů musí být alespoň 1.', 1)
            ->setOption('description', 'Použije se při prvním nebo resetovaném automatickém párování této skupiny.')
            ->setHtmlAttribute('data-bank-pairing-field', '1');
        $form->setCurrentGroup();

        $emailGroup = $form->addGroup('E-mailová komunikace', false);
        $form->setCurrentGroup($emailGroup);
        $form->addSelect('oAuthId', 'E-mail odesílatele', $oAuthItems)
            ->setPrompt('Vyberte e-mail')
            ->setHtmlAttribute('class', 'ui--emailSelectbox'); // For acceptance testing
        $form->setCurrentGroup();

        $this->addEmailsToForm($form);

        $form->addSubmit('send', $this->groupId !== null ? 'Uložit skupinu' : 'Založit skupinu')
            ->setHtmlAttribute('class', 'btn btn-primary')
            ->setHtmlAttribute('data-test', 'payment-group-submit');

        $form->setDefaults($defaults);

        $form->onError[] = function (BaseForm $form): void {
            $this->formError($form);
        };

        $form->onValidate[] = function (BaseForm $form, ArrayHash $values): void {
            $this->validateEmailTemplatesRequireBankAccount($form, $values);
            $this->validateAutomaticPairingRequiresFio($form, $values);
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
        $v = $form->getValues(ArrayHash::class);

        $originalGroupData = $this->buildDefaultsFromGroup($this->bankAccountItems(), $this->oAuthItems());

        $paymentDefaults = new PaymentDefaults(
            $v->amount === null ? null : MoneyFactory::fromDecimal((string) $v->amount),
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
        $remindersEnabled = $v->emails[EmailType::PAYMENT_REMINDER]?->remindersEnabled ?? false;
        $reminderDaysAfterDue = $this->reminderDaysAfterDue($v->emails[EmailType::PAYMENT_REMINDER]?->reminderDaysAfterDue ?? null);

        if ($this->cloneSourceGroupId !== null && $oAuthId === null) {
            $emails = $this->buildEmailTemplatesFromDefaults($originalGroupData['emails'] ?? []);
            $remindersEnabled = (bool) (
                $originalGroupData['emails'][EmailType::PAYMENT_REMINDER]['remindersEnabled'] ?? false
            );
            $reminderDaysAfterDue = $this->reminderDaysAfterDue(
                $originalGroupData['emails'][EmailType::PAYMENT_REMINDER]['reminderDaysAfterDue'] ?? null,
            );
        }

        if ($this->groupId !== null) {// EDIT
            $this->model->updateGroup(
                $this->groupId,
                $v->name,
                $paymentDefaults,
                $emails,
                $oAuthId,
                $v->bankAccount,
                $remindersEnabled,
                (bool) $v->automaticPairingEnabled,
                $v->pairingDaysBack !== null && $v->pairingDaysBack !== '' ? (int) $v->pairingDaysBack : null,
                $reminderDaysAfterDue,
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
                $remindersEnabled,
                (bool) $v->automaticPairingEnabled,
                $v->pairingDaysBack !== null && $v->pairingDaysBack !== '' ? (int) $v->pairingDaysBack : null,
                $reminderDaysAfterDue,
            );

            $this->flashMessage('Skupina byla založena');
        }

        $this->getPresenter()->redirect(':Payments:Payment:default', ['id' => $this->groupId]);
    }

    private function addAutomaticReminderFields(BaseForm $form, Container $container, string $remindersId): void
    {
        $daysId = $remindersId.'_days';

        $remindersEnabled = $container->addCheckbox('remindersEnabled', 'Automaticky odeslat upomínku po splatnosti')
            ->setOption('id', $remindersId)
            ->setHtmlAttribute('data-email-field', '1');

        $days = $container->addText('reminderDaysAfterDue', 'Počet dní po splatnosti')
            ->setHtmlType('number')
            ->setHtmlAttribute('min', '1')
            ->setHtmlAttribute('max', (string) Group::MAX_REMINDER_DAYS_AFTER_DUE)
            ->setHtmlAttribute('data-email-field', '1')
            ->setNullable()
            ->setOption('id', $daysId)
            ->setOption(
                'description',
                'Upomínka odejde automaticky jen jednou, až od splatnosti uplyne zadaný počet dní. Platby, které jsou po splatnosti už déle, ji dostanou při nejbližším automatickém odeslání. Platbě, které už upomínka odešla ručně, automatická neodejde.',
            );

        $enabled = $container['enabled'];
        if (! $enabled instanceof Checkbox) {
            throw new LogicException('Assertion failed.');
        }

        $days->addConditionOn($enabled, $form::FILLED)
            ->addConditionOn($remindersEnabled, $form::FILLED)
            ->toggle($daysId)
            ->setRequired('Zadejte, kolik dní po splatnosti se má upomínka odeslat.')
            ->addRule($form::INTEGER, 'Počet dní musí být celé číslo.')
            ->addRule($form::MIN, 'Počet dní musí být alespoň 1.', 1)
            ->addRule($form::MAX, 'Počet dní může být nejvýš %d.', Group::MAX_REMINDER_DAYS_AFTER_DUE);
    }

    /**
     * The field is checked only while automatic reminders are on. With them off it may still send anything,
     * which is then not kept rather than breaking the save.
     */
    private function reminderDaysAfterDue(mixed $value): ?int
    {
        if (! is_numeric($value) || (string) (int) $value !== (string) $value) {
            return null;
        }

        $days = (int) $value;

        return $days >= 1 && $days <= Group::MAX_REMINDER_DAYS_AFTER_DUE ? $days : null;
    }

    private function validateEmailTemplatesRequireBankAccount(BaseForm $form, ArrayHash $values): void
    {
        if ($values->bankAccount !== null && $values->bankAccount !== '') {
            return;
        }

        $emails = [
            $this->buildEmailTemplate($values, EmailType::PAYMENT_INFO),
            $this->buildEmailTemplate($values, EmailType::PAYMENT_COMPLETED),
            $this->buildEmailTemplate($values, EmailType::PAYMENT_REMINDER),
        ];

        foreach ($emails as $email) {
            if ($email?->requiresBankAccount()) {
                $bankAccount = $form['bankAccount'];
                if (! $bankAccount instanceof SelectBox) {
                    throw new LogicException('Assertion failed.');
                }

                $bankAccount->addError(
                    'Šablonu s proměnnou %account% nebo %qrcode% nelze uložit bez bankovního účtu. Upravte šablonu, nebo připojte bankovní účet.',
                );

                return;
            }
        }
    }

    /** The cron pairs only FIO accounts, a GPC statement is paired when it is imported. */
    private function validateAutomaticPairingRequiresFio(BaseForm $form, ArrayHash $values): void
    {
        if (! $values->automaticPairingEnabled || ! in_array($values->bankAccount, $this->gpcBankAccountIds(), true)) {
            return;
        }

        $automaticPairing = $form['automaticPairingEnabled'];
        if (! $automaticPairing instanceof Checkbox) {
            throw new LogicException('Assertion failed.');
        }

        $automaticPairing->addError(self::FIO_ONLY_PAIRING_MESSAGE);
    }

    private function getDefaultEmailBody(string $name): string
    {
        return FileSystem::read(__DIR__.'/../../Presentation/Payments/InvoiceSequenceList/defaultEmails/'.$name.'.html');
    }

    /**
     * @param array<int, string>                   $bankAccountItems
     * @param array<string, array<string, string>> $oAuthItems
     *
     * @return mixed[]
     */
    private function buildDefaultsFromGroup(array $bankAccountItems, array $oAuthItems): array
    {
        $sourceGroupId = $this->groupId ?? $this->cloneSourceGroupId;

        if ($sourceGroupId === null) {
            return [];
        }

        $group = $this->model->getGroup($sourceGroupId);

        Assertion::notNull($group);

        $emails = [];

        foreach (EmailType::getAvailableEnums() as $emailType) {
            $emails[$emailType->toString()] = $this->getEmailDefaults($sourceGroupId, $emailType);
            if ($emailType->toString() !== EmailType::PAYMENT_REMINDER) {
                continue;
            }

            // Without the chosen days the automatic reminder does not go out, so the switch shows as off.
            $emails[$emailType->toString()]['remindersEnabled'] = $group->isRemindersEnabled() && $group->getReminderDaysAfterDue() !== null;
            $emails[$emailType->toString()]['reminderDaysAfterDue'] = $group->getReminderDaysAfterDue();
        }

        $defaults = [
            'name' => $group->getName(),
            'amount' => $group->getDefaultAmount() === null ? null : MoneyFactory::toDecimal($group->getDefaultAmount()),
            'dueDate' => $group->getDueDate(),
            'constantSymbol' => $group->getConstantSymbol(),
            'oAuthId' => $this->isOAuthAvailable($group->getOAuthId()?->toString(), $oAuthItems)
                ? $group->getOAuthId()?->toString()
                : null,
            'emails' => $emails,
            'bankAccount' => $group->getBankAccountId() !== null
                && array_key_exists($group->getBankAccountId(), $bankAccountItems)
                    ? $group->getBankAccountId()
                    : null,
            'automaticPairingEnabled' => $group->isAutomaticPairingEnabled(),
            'pairingDaysBack' => $group->getPairingDaysBack() ?? BankService::DAYS_BACK_DEFAULT,
        ];

        if ($this->groupId !== null) {
            $defaults['nextVs'] = $group->getNextVariableSymbol();
            $defaults['groupId'] = $this->groupId;
        }

        return $defaults;
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
                $this->addAutomaticReminderFields($form, $container, $remindersId);
            }

            $defaultSubjects = [
                EmailType::PAYMENT_INFO => 'Platební údaje – %name%',
                EmailType::PAYMENT_COMPLETED => 'Potvrzení o úhradě – %name%',
                EmailType::PAYMENT_REMINDER => 'Upomínka platby – %name%',
            ];

            $container->addText('subject', 'Předmět e-mailu')
                ->setOption('id', $subjectId)
                ->setHtmlAttribute('data-email-field', '1')
                ->setDefaultValue($defaultSubjects[$type]);
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

    /**
     * @param array<string, mixed[]> $emailDefaults
     *
     * @return array<string, EmailTemplate>
     */
    private function buildEmailTemplatesFromDefaults(array $emailDefaults): array
    {
        $emails = [];

        foreach (EmailType::getAvailableValues() as $emailType) {
            $defaults = $emailDefaults[$emailType] ?? [];

            if (
                ! isset($defaults['subject'], $defaults['body'])
                || ($emailType !== EmailType::PAYMENT_INFO && ! ($defaults['enabled'] ?? false))
            ) {
                continue;
            }

            $emails[$emailType] = new EmailTemplate(
                (string) $defaults['subject'],
                (string) $defaults['body'],
            );
        }

        return $emails;
    }

    /** @return mixed[] */
    private function getEmailDefaults(int $groupId, EmailType $type): array
    {
        $email = $this->queryBus->handle(new GroupEmailQuery($groupId, $type));

        if ($email === null) {
            return [];
        }

        if (! $email instanceof GroupEmail) {
            throw new LogicException('Assertion failed.');
        }

        return [
            'enabled' => $email->isEnabled(),
            'subject' => $email->getTemplate()->getSubject(),
            'body' => $email->getTemplate()->getBody(),
        ];
    }

    /** @return array<int, string> */
    private function bankAccountItems(): array
    {
        $items = [];

        foreach ($this->accessibleBankAccounts() as $bankAccount) {
            $items[$bankAccount->getId()] = $bankAccount->getName();
        }

        return $items;
    }

    /** @return list<int> */
    private function gpcBankAccountIds(): array
    {
        return array_values(array_map(
            static fn (BankAccount $bankAccount): int => $bankAccount->getId(),
            array_filter(
                $this->accessibleBankAccounts(),
                static fn (BankAccount $bankAccount): bool => $bankAccount->getTransactionSource() === BankTransactionSource::GPC,
            ),
        ));
    }

    /** @return list<BankAccount> */
    private function accessibleBankAccounts(): array
    {
        if ($this->accessibleBankAccounts !== null) {
            return $this->accessibleBankAccounts;
        }

        $this->accessibleBankAccounts = [];

        foreach ($this->queryBus->handle(new BankAccountsAccessibleByUnitsQuery($this->groupUnitIds())) as $bankAccount) {
            if (! $bankAccount instanceof BankAccount) {
                throw new LogicException('Assertion failed.');
            }

            $this->accessibleBankAccounts[] = $bankAccount;
        }

        return $this->accessibleBankAccounts;
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
            if (! $oAuth instanceof OAuth) {
                throw new LogicException('Assertion failed.');
            }
            $unit = $units[$oAuth->getUnitId()];
            if (! $unit instanceof Unit) {
                throw new LogicException('Assertion failed.');
            }
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
        if (! ($group !== null)) {
            throw new LogicException('Assertion failed.');
        }

        return $group->getUnitIds();
    }

    /** @param array<string, array<string, string>> $oAuthItems */
    private function isOAuthAvailable(?string $oAuthId, array $oAuthItems): bool
    {
        if ($oAuthId === null) {
            return false;
        }

        foreach ($oAuthItems as $items) {
            if (array_key_exists($oAuthId, $items)) {
                return true;
            }
        }

        return false;
    }
}
