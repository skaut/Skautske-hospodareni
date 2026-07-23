<?php

declare(strict_types=1);

namespace App\Presentation\Settings\BankAccounts;

use App\Components\DataGrid;
use App\Components\Factories\Payment\IBankAccountFormFactory;
use App\Components\Grids\GridFactory;
use App\Components\Payment\BankAccountDetail\BankAccountDetail;
use App\Components\Payment\BankAccountDetail\BankAccountDetailViewFactory;
use App\Components\Payment\BankAccountDetail\BankAccountManualCandidate;
use App\Components\Payment\BankAccountDetail\BankAccountManualPairingOutcome;
use App\Components\Payment\BankAccountDetail\BankAccountManualPairingService;
use App\Components\Payment\BankAccountDetail\BankAccountTransactionLink;
use App\Components\Payment\BankAccountDetail\BankAccountTransactionRow;
use App\Components\Payment\BankAccountForm;
use App\Components\Payment\GpcImportDialog;
use App\Model\Auth\Resources\InvoiceAccess;
use App\Model\Auth\Resources\Unit as UnitResource;
use App\Model\Bank\BankTransactionAmountMismatch;
use App\Model\Bank\BankTransactionPairingNotAllowed;
use App\Model\Bank\Enum\BankTransactionSource;
use App\Model\Common\UnitId;
use App\Model\DTO\Payment\BankAccount;
use App\Model\Payment\BankAccount\BankAccountId;
use App\Model\Payment\BankAccountNotFound;
use App\Model\Payment\BankAccountService;
use App\Model\Payment\ReadModel\Queries\CountGroupsWithBankAccountQuery;
use App\Model\Payment\ReadModel\Queries\GetGroupList;
use App\Model\Unit\ReadModel\Queries\SubunitListQuery;
use App\Model\Unit\Unit;
use App\Model\User\ReadModel\Queries\ActiveSkautisRoleQuery;
use App\Model\User\SkautisRole;
use App\Presentation\Settings\SettingsBasePresenter;
use InvalidArgumentException;
use Nette\Application\BadRequestException;
use Nette\Utils\Html;
use RuntimeException;

use function array_keys;
use function array_map;
use function array_reduce;
use function array_values;
use function implode;
use function number_format;

final class BankAccountsPresenter extends SettingsBasePresenter
{
    private const TRANSACTION_VIEW_INCOMING = 'incoming';
    private const TRANSACTION_VIEW_ALL = 'all';

    private ?int $id = null;

    private ?BankAccountDetail $detail = null;

    private string $transactionView = self::TRANSACTION_VIEW_INCOMING;

    public function __construct(
        private readonly IBankAccountFormFactory $formFactory,
        private readonly GridFactory $gridFactory,
        private readonly BankAccountService $accounts,
        private readonly BankAccountDetailViewFactory $detailViewFactory,
        private readonly BankAccountManualPairingService $manualPairingService,
    ) {
        parent::__construct();
    }

    public function handleAllowForSubunits(int $id): void
    {
        $this->assertCanEditBankAccount($id);

        try {
            $this->accounts->allowForSubunits($id);
            $this->flashMessage('Bankovní účet zpřístupněn', 'success');
        } catch (BankAccountNotFound) {
            $this->flashMessage('Bankovní účet neexistuje', 'danger');
        }

        $this->redirect('this');
    }

    public function handleDisallowForSubunits(int $id): void
    {
        $this->assertCanEditBankAccount($id);

        try {
            $this->accounts->disallowForSubunits($id);
            $this->flashMessage('Bankovní účet znepřístupněn', 'success');
        } catch (BankAccountNotFound) {
            $this->flashMessage('Bankovní účet neexistuje', 'danger');
        }

        $this->redirect('this');
    }

    public function handleRemove(int $id): void
    {
        $this->assertCanEditBankAccount($id);

        try {
            $this->accounts->removeBankAccount($id);
            $this->flashMessage('Bankovní účet byl odstraněn', 'success');
        } catch (BankAccountNotFound) {
        }

        $this->redirect('default', ['unitId' => $this->getUnitId()]);
    }

    public function handleImport(): void
    {
        if (! $this->canEdit()) {
            $this->noAccess();
        }

        try {
            [$importedCount, $skautisCount, $existingCount] = $this->accounts->importFromSkautis($this->getCurrentUnitId()->toInt());
            $this->flashMessage(
                sprintf('Importováno %d nových účtů (Skautis vrátil %d, v DB bylo %d).', $importedCount, $skautisCount, $existingCount),
                'success',
            );
        } catch (BankAccountNotFound $e) {
            $this->flashMessage($e->getMessage(), 'warning');
        }

        $this->redirect('this');
    }

    public function actionPairTransactionToPayment(int $accountId, string $transactionKey, int $paymentId, ?string $transactionView = null): void
    {
        $this->assertCanViewBankAccount($accountId);
        $transactionView = $this->normalizeTransactionView($transactionView);

        try {
            $outcome = $this->manualPairingService->pairTransactionToPayment(
                $accountId,
                $transactionKey,
                $paymentId,
                $this->getAccessibleGroupIds(),
                $this->userService->getUserDetail()->Person,
            );
            $this->flashManualPairingOutcome($outcome);
        } catch (BankTransactionAmountMismatch|BankTransactionPairingNotAllowed|InvalidArgumentException $e) {
            $this->flashMessage($e->getMessage(), 'danger');
        }

        $this->redirect('detail', ['id' => $accountId, 'unitId' => $this->getUnitId(), 'transactionView' => $transactionView]);
    }

    public function actionPairTransactionToInvoice(int $accountId, string $transactionKey, int $invoiceId, ?string $transactionView = null): void
    {
        $this->assertCanViewBankAccount($accountId);
        $transactionView = $this->normalizeTransactionView($transactionView);

        if (! $this->canAccessInvoices()) {
            $this->flashMessage('Fakturace je zatím dostupná jen uživatelům v testovacím programu.', 'warning');
            $this->redirect('detail', ['id' => $accountId, 'unitId' => $this->getUnitId(), 'transactionView' => $transactionView]);
        }

        try {
            $outcome = $this->manualPairingService->pairTransactionToInvoice(
                $accountId,
                $transactionKey,
                $invoiceId,
                array_keys($this->unitService->getReadUnits($this->user)),
                $this->userService->getUserDetail()->Person,
            );
            $this->flashManualPairingOutcome($outcome);
        } catch (BankTransactionAmountMismatch|BankTransactionPairingNotAllowed|InvalidArgumentException $e) {
            $this->flashMessage($e->getMessage(), 'danger');
        }

        $this->redirect('detail', ['id' => $accountId, 'unitId' => $this->getUnitId(), 'transactionView' => $transactionView]);
    }

    public function actionEdit(int $id): void
    {
        $this->assertCanEditBankAccount($id);
        $this->id = $id;
    }

    protected function beforeRender(): void
    {
        parent::beforeRender();

        $this->setSettingsTemplateParameters();
    }

    public function renderEdit(int $id): void
    {
        $canAccessInvoices = $this->canAccessInvoices();
        $groupsCount = $this->queryBus->handle(new CountGroupsWithBankAccountQuery(new BankAccountId($id)));
        $invoiceSequencesCount = $canAccessInvoices ? $this->accounts->countInvoiceSequencesUsingBankAccount($id) : 0;
        $subunitGroupsCount = $this->accounts->countGroupsDetachedByDisallowForSubunits($id);
        $subunitInvoiceSequencesCount = $canAccessInvoices
            ? $this->accounts->countInvoiceSequencesDetachedByDisallowForSubunits($id)
            : 0;

        $this->template->setParameters([
            'account' => $this->findBankAccount($id),
            'canAccessInvoices' => $canAccessInvoices,
            'groupsCount' => $groupsCount,
            'invoiceSequencesCount' => $invoiceSequencesCount,
            'subunitGroupsCount' => $subunitGroupsCount,
            'subunitInvoiceSequencesCount' => $subunitInvoiceSequencesCount,
            'disallowSubunitsConfirm' => $canAccessInvoices
                ? sprintf(
                    'Opravdu chceš odebrat přístup oddílům? Účet se odpojí z %d platebních skupin a %d fakturačních řad podřízených jednotek. Historie spárování zůstane zachována.',
                    $subunitGroupsCount,
                    $subunitInvoiceSequencesCount,
                )
                : sprintf(
                    'Opravdu chceš odebrat přístup oddílům? Účet se odpojí z %d platebních skupin podřízených jednotek. Historie spárování zůstane zachována.',
                    $subunitGroupsCount,
                ),
            'removeConfirm' => $canAccessInvoices
                ? sprintf(
                    'Opravdu chceš odstranit tento bankovní účet? Účet se odpojí z %d platebních skupin a %d fakturačních řad, jeho importy a transakce se smažou, ale historické informace u plateb, faktur a auditních záznamů párování zůstanou zachovány.',
                    $groupsCount,
                    $invoiceSequencesCount,
                )
                : sprintf(
                    'Opravdu chceš odstranit tento bankovní účet? Účet se odpojí z %d platebních skupin, jeho importy a transakce se smažou, ale historické informace u plateb a auditních záznamů párování zůstanou zachovány.',
                    $groupsCount,
                ),
        ]);
    }

    public function actionDefault(): void
    {
        $this->template->setParameters([
            'canEdit' => $this->canEdit(),
        ]);
    }

    public function actionDetail(int $id, ?int $paymentId = null, ?int $invoiceId = null, ?string $transactionView = null): void
    {
        $this->assertCanViewBankAccount($id);
        $this->transactionView = $this->normalizeTransactionView($transactionView);

        if ($invoiceId !== null && ! $this->canAccessInvoices()) {
            $this->flashMessage('Fakturace je zatím dostupná jen uživatelům v testovacím programu.', 'warning');
            $this->redirect('detail', ['id' => $id, 'unitId' => $this->getUnitId()]);
        }
    }

    public function renderDetail(int $id, ?int $paymentId = null, ?int $invoiceId = null, ?string $transactionView = null): void
    {
        $this->transactionView = $this->normalizeTransactionView($transactionView);
        $account = $this->accounts->find($id) ?? throw new RuntimeException('Bankovní účet nebyl nalezen.');
        $readableUnitIds = array_keys($this->unitService->getReadUnits($this->user));
        $groupNames = $this->resolveGroupNames($readableUnitIds);
        $canAccessInvoices = $this->canAccessInvoices();
        $this->detail = $this->detailViewFactory->create($id, $groupNames, $readableUnitIds, $paymentId, $invoiceId, $canAccessInvoices, $this->transactionView);

        $this->template->setParameters([
            'account' => $account,
            'canAccessInvoices' => $canAccessInvoices,
            'groupNames' => $groupNames,
            'transactionRows' => $this->detail->transactionRows,
            'importBatches' => $this->detail->importBatches,
            'focusTargetLabel' => $this->detail->focusTargetLabel,
            'warningMessage' => $this->detail->warningMessage,
            'errorMessage' => $this->detail->errorMessage,
            'canImportGpc' => $this->canImportGpcForAccount($account),
            'transactionView' => $this->transactionView,
            'transactionViewIncoming' => self::TRANSACTION_VIEW_INCOMING,
            'transactionViewAll' => self::TRANSACTION_VIEW_ALL,
            'paymentId' => $paymentId,
            'invoiceId' => $invoiceId,
        ]);
    }

    protected function createComponentForm(): BankAccountForm
    {
        return $this->formFactory->create($this->id);
    }

    protected function createComponentGrid(): DataGrid
    {
        $grid = $this->gridFactory->createSimpleGrid();
        $accounts = $this->accounts->findByUnit($this->getCurrentUnitId());
        $gpcImportableAccountIds = $this->resolveGpcImportableAccountIds($accounts);

        $grid->addColumnLink('name', 'Název', 'detail', null, ['id' => 'id'])
            ->setSortable();

        $grid->addColumnText('number', 'Číslo účtu')
            ->setSortable();

        $grid->addColumnText('source', 'Zdroj transakcí')
            ->setRenderer(static function (array $account): Html|string {
                if ($account['source'] === '') {
                    return '';
                }

                $isFio = $account['sourceType'] === BankTransactionSource::FIO->value;
                $badge = Html::el('span')
                    ->setAttribute('class', $isFio ? 'badge bg-success' : 'badge bg-secondary');
                $badge->addHtml(
                    Html::el('i')->setAttribute(
                        'class',
                        $isFio ? 'fi fi-rr-check' : 'fi fi-rr-file-upload',
                    ),
                );
                $badge->addText(' '.$account['source']);

                return $badge;
            });

        $grid->addColumnDateTime('createdAt', 'Přidáno')
            ->setFormat('j.n. Y')
            ->setSortable();

        $grid->addFilterText('search', '', ['name', 'number'])
            ->setPlaceholder('Hledat účet...');

        $grid->addAction('detail', '', 'detail', ['id' => 'id'])
            ->setIcon('fi fi-rr-search')
            ->setTitle('Detail')
            ->setClass('btn btn-sm btn-light');

        $grid->addAction('gpcImport', '', 'gpcImportDialog:open!', ['bankAccountId' => 'id'])
            ->setIcon('fi fi-rr-file-import')
            ->setTitle('Importovat GPC soubor')
            ->setClass('btn btn-sm btn-light ajax')
            ->setRenderCondition(
                static fn (array $account): bool => isset($gpcImportableAccountIds[$account['id']]),
            );

        $grid->addAction('settings', '', 'edit', ['id' => 'id'])
            ->setIcon('fi fi-rr-settings')
            ->setTitle('Nastavení')
            ->setClass('btn btn-sm btn-light');

        $grid->setDefaultSort(['name' => DataGrid::SORT_ASC]);
        $grid->setDataSource(array_map(
            static fn (BankAccount $account): array => [
                'id' => $account->getId(),
                'name' => $account->getName(),
                'number' => (string) $account->getNumber(),
                'source' => (
                    $account->getTransactionSource()->value === BankTransactionSource::FIO->value
                    && $account->getToken() === null
                ) ? '' : $account->getTransactionSource()->label(),
                'sourceType' => $account->getTransactionSource()->value,
                'createdAt' => $account->getCreatedAt(),
            ],
            $accounts,
        ));

        return $grid;
    }

    protected function createComponentTransactionsGrid(): DataGrid
    {
        $grid = $this->gridFactory->createSimpleGrid();
        $grid->setPrimaryKey('transactionKey');

        $grid->addColumnDateTime('date', 'Datum')
            ->setFormat('j.n. Y')
            ->setSortable();

        $grid->addColumnText('amount', 'Částka')
            ->setRenderer(fn (array $row): Html => $this->formatTransactionAmount((float) $row['amount']))
            ->setSortable('amountSort');

        $grid->addColumnText('counterAccount', 'Účet')
            ->setSortable();

        $grid->addColumnText('counterName', 'Jméno')
            ->setSortable();

        $grid->addColumnText('constantSymbol', 'KS')
            ->setSortable();

        $grid->addColumnText('variableSymbol', 'VS')
            ->setSortable();

        $grid->addColumnText('note', 'Poznámka');

        $grid->addColumnText('status', 'Stav / kandidáti')
            ->setRenderer(fn (array $row): Html => $this->formatTransactionStatus($row['row']));

        $grid->addFilterText('search', '', ['counterAccount', 'counterName', 'constantSymbol', 'variableSymbol', 'note', 'statusSearch'])
            ->setPlaceholder('Hledat transakci...');

        $grid->setDefaultSort(['date' => DataGrid::SORT_DESC]);
        $grid->setDataSource($this->buildTransactionGridRows());

        return $grid;
    }

    protected function createComponentGpcImportDialog(): GpcImportDialog
    {
        $dialog = new GpcImportDialog(
            $this->accounts,
            $this->userService,
            fn (int $bankAccountId): bool => $this->canImportGpcForAccountId($bankAccountId),
        );

        $dialog->onSuccess[] = function (int $bankAccountId): void {
            if (! $this->isAjax()) {
                $this->redirect('this');
            }

            if ($this->getAction() === 'detail' && (int) $this->getParameter('id') === $bankAccountId) {
                $this->redrawControl('transactions');
                $this->redrawControl('importBatches');
            }
        };

        return $dialog;
    }

    private function noAccess(): void
    {
        $this->setView('accessDenied');
    }

    private function canEdit(?int $unitId = null): bool
    {
        return $this->authorizator->isAllowed(UnitResource::EDIT, $unitId ?? $this->getUnitId());
    }

    /**
     * @param  int[]              $readableUnitIds
     * @return array<int, string>
     */
    private function resolveGroupNames(array $readableUnitIds): array
    {
        $groupNames = [];

        foreach ($this->queryBus->handle(new GetGroupList($readableUnitIds, false)) as $group) {
            $groupNames[$group->getId()] = $group->getName();
        }

        return $groupNames;
    }

    /** @return list<array<string, mixed>> */
    private function buildTransactionGridRows(): array
    {
        $detail = $this->detail ?? $this->resolveDetailForCurrentRequest();
        if ($detail->transactionRows === null) {
            return [];
        }

        $rows = array_map(
            static function (BankAccountTransactionRow $row): array {
                $transaction = $row->transaction;

                return [
                    'transactionKey' => $transaction->getTransactionKey(),
                    'date' => $transaction->getDate(),
                    'amount' => $transaction->getAmount(),
                    'amountSort' => sprintf('%020.2f', $transaction->getAmount() + 1000000000),
                    'counterAccount' => $transaction->getCounterAccount() ?? '',
                    'counterName' => $transaction->getCounterName(),
                    'constantSymbol' => $transaction->getConstantSymbol() !== null ? (string) $transaction->getConstantSymbol() : '',
                    'variableSymbol' => $transaction->getVariableSymbol() !== null ? (string) $transaction->getVariableSymbol() : '',
                    'note' => $transaction->getNote() ?? '',
                    'statusSearch' => self::buildStatusSearchText($row),
                    'row' => $row,
                ];
            },
            $detail->transactionRows,
        );

        if ($this->transactionView === self::TRANSACTION_VIEW_ALL) {
            return $rows;
        }

        return array_values(array_filter(
            $rows,
            static fn (array $row): bool => (float) $row['amount'] > 0,
        ));
    }

    private function resolveDetailForCurrentRequest(): BankAccountDetail
    {
        $id = (int) $this->getParameter('id');
        $paymentId = $this->getParameter('paymentId');
        $invoiceId = $this->getParameter('invoiceId');
        $readableUnitIds = array_keys($this->unitService->getReadUnits($this->user));

        return $this->detailViewFactory->create(
            $id,
            $this->resolveGroupNames($readableUnitIds),
            $readableUnitIds,
            $paymentId !== null ? (int) $paymentId : null,
            $invoiceId !== null ? (int) $invoiceId : null,
            $this->canAccessInvoices(),
            $this->transactionView,
        );
    }

    private function normalizeTransactionView(?string $transactionView): string
    {
        return $transactionView === self::TRANSACTION_VIEW_ALL
            ? self::TRANSACTION_VIEW_ALL
            : self::TRANSACTION_VIEW_INCOMING;
    }

    private function formatTransactionAmount(float $amount): Html
    {
        $strong = Html::el('strong')
            ->setText(number_format($amount, 2, ',', ' ').' Kč');

        if ($amount < 0) {
            $strong->setAttribute('class', 'text-danger');
        }

        return Html::el('div')
            ->setAttribute('class', 'text-end')
            ->addHtml($strong);
    }

    private function formatTransactionStatus(BankAccountTransactionRow $row): Html
    {
        $container = Html::el('div')
            ->setAttribute('class', 'd-flex flex-column gap-1');

        if ($row->pairingLabel !== null) {
            $pairing = Html::el('div');
            $pairing->addHtml(Html::el('span')->setAttribute('class', 'badge bg-info text-dark')->setText('Spárováno'));
            $pairing->addText(' ');
            $pairing->addHtml($this->formatTransactionLink($row->pairingLabel));
            $container->addHtml($pairing);
        }

        if ($row->manualCandidates !== []) {
            $container->addHtml(Html::el('div')->setAttribute('class', 'small text-body-secondary')->setText('Ruční párování podle částky:'));

            foreach ($row->manualCandidates as $candidate) {
                $container->addHtml($this->formatManualCandidate($candidate));
            }
        }

        if ($row->exactCandidates !== []) {
            $container->addHtml(Html::el('div')->setAttribute('class', 'small text-body-secondary')->setText('Jednoznačné automatické shody:'));

            foreach ($row->exactCandidates as $candidate) {
                $container->addHtml($this->formatCandidateLink($candidate));
            }
        }

        if ($row->conflictReason !== null) {
            $container->addHtml(Html::el('div')->setAttribute('class', 'small text-danger')->setText($row->conflictReason));
        }

        if ($row->variableSymbolCandidates !== [] && $row->exactCandidates === []) {
            $container->addHtml(Html::el('div')->setAttribute('class', 'small text-body-secondary')->setText('Položky se shodným VS:'));

            foreach ($row->variableSymbolCandidates as $candidate) {
                $container->addHtml($this->formatCandidateLink($candidate));
            }
        }

        return $container;
    }

    private function formatManualCandidate(BankAccountManualCandidate $candidate): Html
    {
        $container = Html::el('div')->setAttribute('class', 'mb-1');
        $container->addHtml($this->formatTypeBadge($candidate->type));
        $container->addText(' ');
        $container->addHtml(Html::el('a')->href($candidate->url)->setText($candidate->label));
        $container->addText(' ');
        $container->addHtml(
            Html::el('a')
                ->href($candidate->actionUrl)
                ->setAttribute('class', 'btn btn-sm btn-outline-success ms-1')
                ->setAttribute('data-confirm', 'Opravdu chceš ručně spárovat tuto bankovní transakci?')
                ->setText('Spárovat'),
        );

        if ($candidate->warnings !== []) {
            $container->addText(' ');
            $container->addHtml(Html::el('span')->setAttribute('class', 'small text-warning ms-1')->setText(implode(', ', $candidate->warnings)));
        }

        return $container;
    }

    private function formatCandidateLink(BankAccountTransactionLink $candidate): Html
    {
        $container = Html::el('div');
        $container->addHtml($this->formatTypeBadge($candidate->type));
        $container->addText(' ');
        $container->addHtml($this->formatTransactionLink($candidate));

        return $container;
    }

    private function formatTransactionLink(BankAccountTransactionLink $link): Html
    {
        if ($link->url === null) {
            return Html::el('span')->setText($link->label);
        }

        return Html::el('a')
            ->href($link->url)
            ->setText($link->label);
    }

    private function formatTypeBadge(string $type): Html
    {
        return Html::el('span')
            ->setAttribute('class', $type === 'invoice' ? 'badge bg-secondary' : 'badge bg-primary')
            ->setText($type === 'invoice' ? 'Faktura' : 'Platba');
    }

    private static function buildStatusSearchText(BankAccountTransactionRow $row): string
    {
        $parts = [];

        foreach ([$row->pairingLabel, ...$row->exactCandidates, ...$row->variableSymbolCandidates] as $link) {
            if ($link instanceof BankAccountTransactionLink) {
                $parts[] = $link->label;
            }
        }

        foreach ($row->manualCandidates as $candidate) {
            $parts[] = $candidate->label;
            $parts[] = implode(' ', $candidate->warnings);
        }

        if ($row->conflictReason !== null) {
            $parts[] = $row->conflictReason;
        }

        return implode(' ', $parts);
    }

    private function canAccessInvoices(): bool
    {
        return $this->authorizator->isAllowed(InvoiceAccess::ACCESS, null);
    }

    private function assertCanViewBankAccount(int $id): void
    {
        $account = $this->accounts->find($id);

        if ($account === null) {
            throw new BadRequestException('Bankovní účet neexistuje');
        }

        if ($this->canEdit($account->getUnitId())) {
            return;
        }

        if ($account->isAllowedForSubunits() && $this->isSubunitOf($account->getUnitId())) {
            return;
        }

        $role = $this->queryBus->handle(new ActiveSkautisRoleQuery());
        if (! $role instanceof SkautisRole) {
            throw new BadRequestException('Nepodařilo se ověřit roli uživatele');
        }

        if (
            $role->getUnitId() === $account->getUnitId()
            && $role->isBasicUnit()
            && ($role->isAccountant() || $role->isOfficer() || $role->isEventManager())
        ) {
            return;
        }

        $this->noAccess();
    }

    private function assertCanEditBankAccount(int $id): void
    {
        if (! $this->canEdit()) {
            $this->noAccess();
        }

        $account = $this->findBankAccount($id);
        if ($account === null) {
            throw new BadRequestException('Bankovní účet neexistuje');
        }

        if ($this->canEdit($account->getUnitId())) {
            return;
        }

        $this->noAccess();
    }

    private function isSubunitOf(int $unitId): bool
    {
        $currentUnitId = $this->getCurrentUnitId()->toInt();

        foreach ($this->queryBus->handle(new SubunitListQuery(UnitId::fromInt($unitId))) as $subunit) {
            if (! $subunit instanceof Unit) {
                continue;
            }

            if ($subunit->getId() === $currentUnitId) {
                return true;
            }
        }

        return false;
    }

    private function findBankAccount(int $id): ?BankAccount
    {
        return $this->accounts->find($id);
    }

    /**
     * @param  BankAccount[]    $accounts
     * @return array<int, true>
     */
    private function resolveGpcImportableAccountIds(array $accounts): array
    {
        return array_reduce(
            $accounts,
            function (array $importableAccountIds, BankAccount $account): array {
                if ($this->canImportGpcForAccount($account)) {
                    $importableAccountIds[$account->getId()] = true;
                }

                return $importableAccountIds;
            },
            [],
        );
    }

    /** @return int[] */
    private function getAccessibleGroupIds(): array
    {
        $readableUnitIds = array_keys($this->unitService->getReadUnits($this->user));
        $groups = $this->queryBus->handle(new GetGroupList($readableUnitIds, false));

        return array_map(static fn ($group): int => $group->getId(), $groups);
    }

    private function flashManualPairingOutcome(BankAccountManualPairingOutcome $outcome): void
    {
        $this->flashMessage($outcome->successMessage, 'success');

        foreach ($outcome->warnings as $warning) {
            $this->flashMessage($warning, 'warning');
        }
    }

    private function canImportGpcForAccountId(int $id): bool
    {
        $account = $this->findBankAccount($id);

        return $account !== null && $this->canImportGpcForAccount($account);
    }

    private function canImportGpcForAccount(BankAccount $account): bool
    {
        return $this->canEdit($account->getUnitId())
            && $account->getTransactionSource()->value === BankTransactionSource::GPC->value;
    }
}
