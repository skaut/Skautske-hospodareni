<?php

declare(strict_types=1);

namespace App\Presentation\Payments\InvoiceSequenceList;

use App\Components\DataGrid;
use App\Components\Grids\GridFactory;
use App\Model\Bank\Entity\BankAccount;
use App\Model\Bank\InvoiceBankService;
use App\Model\Bank\Repository\BankAccountRepository;
use App\Model\DTO\Google\OAuth;
use App\Model\DTO\Payment\BankAccount as AccessibleBankAccount;
use App\Model\Google\OAuthId;
use App\Model\Infrastructure\Repository\RepositoryService;
use App\Model\Invoice\EmailTemplate;
use App\Model\Invoice\EmailType;
use App\Model\Invoice\Entity\InvoiceSequence;
use App\Model\Invoice\Manager\InvoiceSequenceManager;
use App\Model\Invoice\Repository\InvoiceRepository;
use App\Model\Invoice\Repository\InvoiceSequenceRepository;
use App\Model\Payment\ReadModel\Queries\BankAccount\BankAccountsAccessibleByUnitsQuery;
use App\Model\Payment\ReadModel\Queries\OAuthsAccessibleByGroupsQuery;
use App\Model\Payment\Services\VariableSymbolCollisionChecker;
use App\Model\Payment\VariableSymbolCollision;
use App\Model\Unit\ReadModel\Queries\UnitsDetailQuery;
use App\Model\Unit\Unit;
use App\Model\Unit\UnitService;
use App\Model\User\ReadModel\Queries\ActiveSkautisRoleQuery;
use App\Model\User\SkautisRole;
use App\Presentation\Payments\PaymentsBasePresenter;
use Component\Forms\BaseForm;
use Illuminate\Support\Collection;
use LogicException;
use Nette\Forms\Controls\TextInput;
use Nette\Forms\Form;
use Nette\Utils\ArrayHash;
use Nette\Utils\FileSystem;
use Throwable;
use Ublaboo\DataGrid\Column\Action\Confirmation\StringConfirmation;

use function array_filter;
use function array_keys;
use function array_map;
use function array_unique;
use function assert;
use function count;
use function preg_match;
use function strlen;

class InvoiceSequenceListPresenter extends PaymentsBasePresenter
{
    protected ?int $groupId = null;
    private ?InvoiceSequence $editedSequence = null;

    public function __construct(
        private readonly GridFactory $gridFactory,
        protected InvoiceSequenceManager $invoiceSequenceManager,
        protected InvoiceSequenceRepository $invoiceSequenceRepository,
        protected InvoiceRepository $invoiceRepository,
        protected UnitService $unitRepository,
        protected BankAccountRepository $bankAccountRepository,
        protected RepositoryService $repositoryService,
        private readonly VariableSymbolCollisionChecker $variableSymbolCollisionChecker,
    ) {
        parent::__construct();
    }

    protected function createComponentGrid(): DataGrid
    {
        $grid = $this->gridFactory->createSimpleGrid(
            __DIR__.'/grid.latte',
            [],
        );

        $grid->addColumnNumber('year', 'Rok')
            ->setSortable()
            ->setFilterText();
        $grid->addColumnText('unit', 'Jednotka')
            ->setSortable();

        $grid->addColumnText('description', 'Popis')
            ->setSortable();
        $grid->addColumnNumber('invoiceCount', 'Počet faktur')
            ->setSortable();
        $grid->addColumnText('sequence', 'Řada')
            ->setSortable()
            ->setFilterText();
        $grid->addColumnText('state', 'Stav')
            ->setSortable();

        $grid->addAction('edit', '', ':Payments:InvoiceList:default', ['invoiceSequenceId' => 'id'])
            ->setIcon('far fa-file-lines')
            ->setTitle('Faktury')
            ->setClass('btn btn-sm btn-secondary');

        $grid->addAction('settings', '', ':Payments:InvoiceSequence:edit', ['id' => 'id'])
            ->setIcon('far fa-pen-to-square')
            ->setTitle('Nastavení řady')
            ->setClass('btn btn-sm btn-secondary');

        $grid->addAction('close', '', 'close!', ['id' => 'id'])
            ->setIcon('far fa-circle-xmark')
            ->setTitle('Uzavřít řadu')
            ->setClass('btn btn-sm btn-warning')
            ->setConfirmation(
                new StringConfirmation('Opravdu chceš uzavřít řadu %s?', 'sequence'),
            );

        $grid->addAction('reopen', '', 'reopen!', ['id' => 'id'])
            ->setIcon('far fa-circle-check')
            ->setTitle('Znovu otevřít řadu')
            ->setClass('btn btn-sm btn-success');

        $grid->addAction('delete', '', 'remove!', ['id' => 'id'])
            ->setIcon('far fa-trash-can')
            ->setTitle('Smazat fakturační řadu')
            ->setClass('btn btn-sm btn-danger')
            ->setConfirmation(
                new StringConfirmation('Opravdu chceš smazat řádek %s?', 'sequence'),
            );

        $grid->addFilterText('search', '', ['year', 'description', 'sequence'])
            ->setPlaceholder('Hledej...');

        $grid->setDataSource($this->invoiceSequenceRepository->getGridByUnits($this->getReadableUnitIds()));

        return $grid;
    }

    public function handleRemove(int $id): void
    {
        $invoiceSequence = $this->invoiceSequenceRepository->findAccessibleByUnits($id, $this->getEditableUnits());

        if (! $invoiceSequence instanceof InvoiceSequence) {
            $this->flashMessage('Fakturační řada nebyla nalezena nebo ji nelze upravovat.', 'danger');

            return;
        }

        try {
            $this->invoiceSequenceManager->delete($invoiceSequence);
        } catch (Throwable) {
        }
    }

    public function handleClose(int $id): void
    {
        $invoiceSequence = $this->invoiceSequenceRepository->findAccessibleByUnits($id, $this->getEditableUnits());

        if (! $invoiceSequence instanceof InvoiceSequence) {
            $this->flashMessage('Fakturační řada nebyla nalezena nebo ji nelze upravovat.', 'danger');

            return;
        }

        $invoiceSequence->close();
        $this->invoiceSequenceManager->update($invoiceSequence);
        $this->flashMessage('Fakturační řada byla uzavřena.');
        $this->redirect('this');
    }

    public function handleReopen(int $id): void
    {
        $invoiceSequence = $this->invoiceSequenceRepository->findAccessibleByUnits($id, $this->getEditableUnits());

        if (! $invoiceSequence instanceof InvoiceSequence) {
            $this->flashMessage('Fakturační řada nebyla nalezena nebo ji nelze upravovat.', 'danger');

            return;
        }

        $invoiceSequence->reopen();
        $this->invoiceSequenceManager->update($invoiceSequence);
        $this->flashMessage('Fakturační řada byla znovu otevřena.');
        $this->redirect('this');
    }

    public function actionEdit(int $id): void
    {
        $invoiceSequence = $this->invoiceSequenceRepository->findAccessibleByUnits($id, $this->getEditableUnits());

        if (! $invoiceSequence instanceof InvoiceSequence) {
            $this->flashMessage('Fakturační řada nebyla nalezena.', 'danger');
            $this->redirect('default');
        }

        $this->editedSequence = $invoiceSequence;
        $this->template->setParameters(['invoiceSequence' => $invoiceSequence]);
    }

    protected function beforeRender(): void
    {
        parent::beforeRender();
        $this->template->emailConfigurationUnavailableReason = $this->getEmailConfigurationUnavailableReason($this->editedSequence);
    }

    protected function createComponentCreateForm(): BaseForm
    {
        return $this->createSequenceForm();
    }

    protected function createComponentEditForm(): BaseForm
    {
        if (! $this->editedSequence instanceof InvoiceSequence) {
            throw new LogicException('Fakturační řada pro editaci není načtena.');
        }

        return $this->createSequenceForm($this->editedSequence);
    }

    private function createSequenceForm(?InvoiceSequence $invoiceSequence = null): BaseForm
    {
        $form = new BaseForm();
        $emailConfigurationUnavailableReason = $this->getEmailConfigurationUnavailableReason($invoiceSequence);
        $generalGroup = $form->addGroup('Obecné nastavení fakturační řady', false);
        $form->setCurrentGroup($generalGroup);

        $sequence = $form->addText('sequence', 'Prefix')
            ->addFilter(static fn (string $value): string => mb_strtoupper(trim($value)))
            ->addRule(Form::MAX_LENGTH, 'Maximální delka prefixu je 5 znaků', 5);

        $firstNumber = $form->addText('firstNumber', 'První číslo v řadě')
            ->setRequired('První číslo v řadě musí být vyplněné')
            ->setDefaultValue('00001');

        if ($invoiceSequence instanceof InvoiceSequence && $this->invoiceRepository->hasInvoicesInSequence($invoiceSequence)) {
            $sequence->setDisabled();
            $sequence->setOption('description', 'Prefix nelze změnit, protože řada již obsahuje vystavené faktury.');
            $firstNumber->setDisabled();
            $firstNumber->setOption('description', 'První číslo nelze změnit, protože řada již obsahuje vystavené faktury.');
        }

        $form->addYearSelect('year', 'Rok')->setDefaultValue('now');
        $form->addText('description', 'Popis');
        $form->addInteger('defaultDueDate', 'Výchozí datum splatnosti')->setDefaultValue(14);

        $bankAccount = $form->addSelect('bankAccount', 'Bankovní účet', $this->bankAccountItems($invoiceSequence))
            ->setRequired(false)
            ->setPrompt('Vyberte bankovní účet');

        if ($invoiceSequence instanceof InvoiceSequence) {
            $bankAccount->setHtmlAttribute('data-original-value', (string) ($invoiceSequence->getBankAccount()?->getId() ?? ''));
            $bankAccount->setHtmlAttribute(
                'data-bank-account-change-message',
                'Opravdu chceš změnit bankovní účet této fakturační řady? Změna resetuje kurzor bankovního párování této řady. Historie již spárovaných faktur zůstane zachována.',
            );
        }

        $pairingGroup = $form->addGroup('Bankovní párování', false);
        $form->setCurrentGroup($pairingGroup);
        $form->addCheckbox('automaticPairingEnabled', 'Automaticky párovat úhrady v cronu')
            ->setOption('description', 'Cron páruje jen řady, které mají tuto volbu výslovně zapnutou.')
            ->setHtmlAttribute('data-bank-pairing-field', '1');
        $form->addText('pairingDaysBack', 'Rozšířit hledání zpětně o dnů')
            ->setDefaultValue((string) InvoiceBankService::DAYS_BACK_DEFAULT)
            ->setRequired(false)
            ->setNullable()
            ->addRule(Form::INTEGER, 'Počet dnů musí být celé číslo.')
            ->addRule(Form::MIN, 'Počet dnů musí být alespoň 1.', 1)
            ->setOption('description', 'Použije se při prvním nebo resetovaném automatickém párování této řady.')
            ->setHtmlAttribute('data-bank-pairing-field', '1');
        $form->setCurrentGroup();

        $emailGroup = $form->addGroup('E-mailová komunikace', false);
        $form->setCurrentGroup($emailGroup);
        $oAuthId = $form->addSelect('oAuthId', 'E-mail odesílatele', $this->oAuthItems())
            ->setPrompt('Vyberte e-mail')
            ->setHtmlAttribute('class', 'ui--emailSelectbox');

        if (! $this->hasAvailableOAuths()) {
            $oAuthId->setOption('description', 'V systému není dostupný žádný odesílací e-mail.');
        }
        $form->setCurrentGroup();

        $this->addEmailTemplatesToForm($form, $emailConfigurationUnavailableReason);

        $form->addSubmit('send', $invoiceSequence instanceof InvoiceSequence ? 'Uložit řadu' : 'Založit řadu');
        $form->setDefaults($invoiceSequence instanceof InvoiceSequence ? $this->buildDefaultsFromSequence($invoiceSequence) : []);
        $form->onValidate[] = function (BaseForm $form, ArrayHash $values): void {
            $this->validateSequenceForm($form, $values);
        };
        $form->onSuccess[] = function (BaseForm $form): void {
            $this->formSucceeded($form);
        };

        return $form;
    }

    public function formSucceeded(BaseForm $form): void
    {
        $values = $form->getValues();

        $role = $this->queryBus->handle(new ActiveSkautisRoleQuery());
        assert($role instanceof SkautisRole);
        $unit = new \App\Model\Common\UnitId($role->getUnitId());
        $account = $this->repositoryService->castToEntity(BankAccount::class)($values->bankAccount, new Collection()) ?? null;
        $oauthId = OAuthId::fromStringOrNull($values->oAuthId);
        $emailConfigurationUnavailableReason = $this->getEmailConfigurationUnavailableReason($this->editedSequence, $values->oAuthId);
        if ($this->editedSequence instanceof InvoiceSequence) {
            $invoiceSequence = $this->editedSequence;
            if (! $this->invoiceRepository->hasInvoicesInSequence($invoiceSequence)) {
                $invoiceSequence->setSequence($values->sequence);
                $invoiceSequence->setFirstNumber($values->firstNumber);
            }

            $invoiceSequence->setYear((int) $values->year);
            $invoiceSequence->setDescription($values->description);
            $invoiceSequence->setBankAccount($account);
            $invoiceSequence->setOauthId($oauthId);
            $invoiceSequence->setDefaultDueDate($values->defaultDueDate);
            $invoiceSequence->setAutomaticPairingEnabled((bool) $values->automaticPairingEnabled);
            $invoiceSequence->setPairingDaysBack(
                $values->pairingDaysBack !== null && $values->pairingDaysBack !== ''
                    ? (int) $values->pairingDaysBack
                    : null,
            );

            if ($emailConfigurationUnavailableReason === null) {
                $this->syncEmailTemplates($invoiceSequence, $values);
            }

            $this->invoiceSequenceManager->update($invoiceSequence);

            $this->flashMessage('Fakturační řada byla upravena.');
            $this->redirect(':Payments:InvoiceSequenceList:default', ['unitId' => $this->unitId->toInt()]);
        }

        $invoiceSequence = InvoiceSequence::fromForm($unit, $values, $account, $oauthId);
        $invoiceSequence->setSequenceId($this->invoiceSequenceRepository->getNextSequenceId($this->unitId, $values->year));
        $invoiceSequence->setAutomaticPairingEnabled((bool) $values->automaticPairingEnabled);
        $invoiceSequence->setPairingDaysBack(
            $values->pairingDaysBack !== null && $values->pairingDaysBack !== ''
                ? (int) $values->pairingDaysBack
                : null,
        );

        if ($emailConfigurationUnavailableReason === null) {
            foreach ($this->buildEmailTemplates($values) as $type => $template) {
                $invoiceSequence->updateEmail(EmailType::get($type), $template);
            }
        }

        $this->invoiceSequenceManager->create($invoiceSequence);

        $this->flashMessage('Fakturační řada byla založena.');
        $this->redirect(':Payments:Dashboard:default', ['unitId' => $this->unitId->toInt()]);
    }

    /** @return array<int, string> */
    private function bankAccountItems(?InvoiceSequence $invoiceSequence = null): array
    {
        $targetUnitId = $invoiceSequence?->getUnit() ?? $this->getRoleUnitId();
        $bankAccounts = $this->queryBus->handle(new BankAccountsAccessibleByUnitsQuery([$targetUnitId]));
        $items = [];

        foreach ($bankAccounts as $bankAccount) {
            assert($bankAccount instanceof AccessibleBankAccount);
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
        return [$this->unitId->toInt()];
    }

    private function addEmailTemplatesToForm(BaseForm $form, ?string $unavailableReason = null): void
    {
        $emailsContainer = $form->addContainer('emails');

        $emails = [
            EmailType::INVOICE_INFO => 'E-mail s fakturou',
            EmailType::INVOICE_COMPLETED => 'E-mail při dokončení platby',
            EmailType::INVOICE_REMINDER => 'E-mail upomínka faktury',
        ];

        foreach ($emails as $type => $caption) {
            $group = $form->addGroup($caption, false);
            $container = $emailsContainer->addContainer($type);
            $container->setCurrentGroup($group);

            $subjectId = $type.'_subject';
            $bodyId = $type.'_body';

            if ($type !== EmailType::INVOICE_INFO) {
                $container->addCheckbox('enabled', 'Aktivní')
                    ->setOption('class', 'form-check')
                    ->setHtmlAttribute('data-email-field', '1')
                    ->addCondition($form::FILLED)
                    ->toggle($subjectId)
                    ->toggle($bodyId);
            }

            $defaultSubjects = [
                EmailType::INVOICE_INFO => 'Faktura č. %number%',
                EmailType::INVOICE_COMPLETED => 'Potvrzení o úhradě – faktura č. %number%',
                EmailType::INVOICE_REMINDER => 'Upomínka – faktura č. %number%',
            ];

            $subject = $container->addText('subject', 'Předmět e-mailu')
                ->setOption('id', $subjectId)
                ->setHtmlAttribute('data-email-field', '1')
                ->setDefaultValue($defaultSubjects[$type])
                ->setRequired($type === EmailType::INVOICE_INFO ? 'Předmět e-mailu musí být vyplněn' : false);
            $body = $container->addTextArea('body', 'Text e-mailu', null, 15)
                ->setOption('id', $bodyId)
                ->setHtmlAttribute('class', 'form-control')
                ->setHtmlAttribute('data-email-field', '1')
                ->setDefaultValue($this->getDefaultEmailBody($type))
                ->setRequired($type === EmailType::INVOICE_INFO ? 'Text e-mailu musí být vyplněn' : false);
        }

        $form->setCurrentGroup();
    }

    private function getDefaultEmailBody(string $type): string
    {
        return FileSystem::read(__DIR__.'/defaultEmails/'.$type.'.html');
    }

    /** @return array<string, mixed> */
    private function buildDefaultsFromSequence(InvoiceSequence $invoiceSequence): array
    {
        $emails = [];

        foreach ([EmailType::INVOICE_INFO, EmailType::INVOICE_COMPLETED, EmailType::INVOICE_REMINDER] as $type) {
            $emails[$type] = $this->getEmailDefaults($invoiceSequence, EmailType::get($type));
        }

        return [
            'sequence' => $invoiceSequence->getSequence(),
            'firstNumber' => $invoiceSequence->getFirstNumber(),
            'year' => $invoiceSequence->getYear(),
            'description' => $invoiceSequence->getDescription(),
            'bankAccount' => $invoiceSequence->getBankAccount()?->getId(),
            'oAuthId' => $invoiceSequence->getOauthId()?->toString(),
            'defaultDueDate' => $invoiceSequence->getDefaultDueDate(),
            'automaticPairingEnabled' => $invoiceSequence->isAutomaticPairingEnabled(),
            'pairingDaysBack' => $invoiceSequence->getPairingDaysBack() ?? InvoiceBankService::DAYS_BACK_DEFAULT,
            'emails' => $emails,
        ];
    }

    /** @return array<string, EmailTemplate> */
    private function buildEmailTemplates(ArrayHash $values): array
    {
        $emails = [
            EmailType::INVOICE_INFO => $this->buildEmailTemplate($values, EmailType::INVOICE_INFO),
            EmailType::INVOICE_COMPLETED => $this->buildEmailTemplate($values, EmailType::INVOICE_COMPLETED),
            EmailType::INVOICE_REMINDER => $this->buildEmailTemplate($values, EmailType::INVOICE_REMINDER),
        ];

        return array_filter($emails);
    }

    private function buildEmailTemplate(ArrayHash $values, string $type): ?EmailTemplate
    {
        $emails = $values['emails'] ?? null;
        if (! $emails instanceof ArrayHash || ! isset($emails[$type]) || ! $emails[$type] instanceof ArrayHash) {
            return null;
        }

        $emailValues = $emails[$type];

        if ($type !== EmailType::INVOICE_INFO && ! (bool) ($emailValues['enabled'] ?? false)) {
            return null;
        }

        return new EmailTemplate(
            (string) ($emailValues['subject'] ?? ''),
            (string) ($emailValues['body'] ?? ''),
        );
    }

    private function syncEmailTemplates(InvoiceSequence $invoiceSequence, ArrayHash $values): void
    {
        foreach ([EmailType::INVOICE_INFO, EmailType::INVOICE_COMPLETED, EmailType::INVOICE_REMINDER] as $type) {
            $template = $this->buildEmailTemplate($values, $type);

            if ($template === null) {
                $invoiceSequence->disableEmail(EmailType::get($type));

                continue;
            }

            $invoiceSequence->updateEmail(EmailType::get($type), $template);
        }
    }

    /** @return array<string, mixed> */
    private function getEmailDefaults(InvoiceSequence $invoiceSequence, EmailType $type): array
    {
        $template = $invoiceSequence->getEmailTemplate($type);

        if ($template === null) {
            return [];
        }

        return [
            'enabled' => $invoiceSequence->isEmailEnabled($type),
            'subject' => $template->getSubject(),
            'body' => $template->getBody(),
        ];
    }

    private function validateSequenceForm(BaseForm $form, ArrayHash $values): void
    {
        $sequence = $form['sequence'];
        $firstNumber = $form['firstNumber'];
        $bankAccount = $form['bankAccount'];
        $pairingDaysBackControl = $form['pairingDaysBack'];
        assert($sequence instanceof TextInput);
        assert($firstNumber instanceof TextInput);
        assert($bankAccount instanceof \Nette\Forms\Controls\SelectBox);
        assert($pairingDaysBackControl instanceof TextInput);

        $prefix = (string) $values->sequence;
        $firstNumberValue = (string) $values->firstNumber;

        if ($prefix !== '' && preg_match('/^(?:[A-Z]+[0-9]*|[0-9]+)$/', $prefix) !== 1) {
            $sequence->addError('Prefix může obsahovat pouze text, číslo nebo text následovaný číslem.');
        }

        if ($firstNumberValue === '' || preg_match('/^[0-9]{1,10}$/', $firstNumberValue) !== 1) {
            $firstNumber->addError('První číslo musí být číselný řetězec o délce 1 až 10 znaků.');
        }

        if ($firstNumberValue !== '' && preg_match('/^[0]+$/', $firstNumberValue) === 1) {
            $firstNumber->addError('První číslo nesmí být tvořené jen nulami.');
        }

        $numericPrefixLength = preg_match('/\d+$/', $prefix, $matches) === 1 ? strlen($matches[0]) : 0;
        if ($numericPrefixLength + strlen($firstNumberValue) > 10) {
            $firstNumber->addError('Číselná část prefixu spolu s prvním číslem nesmí přesáhnout 10 znaků kvůli VS.');
        }

        $pairingDaysBack = $values->pairingDaysBack;
        if ($pairingDaysBack !== null && $pairingDaysBack !== '' && (int) $pairingDaysBack < 1) {
            $pairingDaysBackControl->addError('Počet dnů musí být alespoň 1.');
        }

        if ((bool) $values->automaticPairingEnabled && ($values->bankAccount === '' || $values->bankAccount === null)) {
            $bankAccount->addError('Automatické párování lze zapnout jen pro řadu s bankovním účtem.');
        }

        if (! $this->editedSequence instanceof InvoiceSequence) {
            return;
        }

        $selectedBankAccountId = $values->bankAccount !== '' && $values->bankAccount !== null
            ? (int) $values->bankAccount
            : null;
        $currentBankAccountId = $this->editedSequence->getBankAccount()?->getId();

        if ($selectedBankAccountId === $currentBankAccountId) {
            return;
        }

        $selectedBankAccount = $selectedBankAccountId !== null
            ? $this->bankAccountRepository->find($selectedBankAccountId)
            : null;

        try {
            $this->variableSymbolCollisionChecker->assertSequenceCanUseBankAccount($this->editedSequence, $selectedBankAccount);
        } catch (VariableSymbolCollision $exception) {
            $bankAccount->addError($exception->getMessage());
        }
    }

    /** @return int[] */
    private function getReadableUnitIds(): array
    {
        return array_keys($this->unitService->getReadUnits($this->user));
    }

    private function getRoleUnitId(): int
    {
        $role = $this->queryBus->handle(new ActiveSkautisRoleQuery());
        assert($role instanceof SkautisRole);

        return $role->getUnitId();
    }

    private function getEmailConfigurationUnavailableReason(?InvoiceSequence $invoiceSequence = null, ?string $selectedOAuthId = null): ?string
    {
        if (! $this->hasAvailableOAuths()) {
            return 'E-mailové šablony lze nastavit až po zpřístupnění odesílacího e-mailu v systému.';
        }

        $selectedOAuthId ??= $invoiceSequence?->getOauthId()?->toString();
        if ($selectedOAuthId === null || $selectedOAuthId === '') {
            return 'Nejprve vyberte e-mail odesílatele a uložte fakturační řadu.';
        }

        if (! $this->hasAvailableOAuthId($selectedOAuthId)) {
            return 'Vybraný e-mail odesílatele už není v systému dostupný. Vyberte jiný a řadu znovu uložte.';
        }

        return null;
    }

    private function hasAvailableOAuths(): bool
    {
        return count($this->getAvailableOAuthIds()) > 0;
    }

    private function hasAvailableOAuthId(string $oAuthId): bool
    {
        return isset($this->getAvailableOAuthIds()[$oAuthId]);
    }

    /** @return array<string, true> */
    private function getAvailableOAuthIds(): array
    {
        $availableIds = [];

        foreach ($this->oAuthItems() as $emailsByUnit) {
            foreach ($emailsByUnit as $oAuthId => $_email) {
                $availableIds[$oAuthId] = true;
            }
        }

        return $availableIds;
    }
}
