<?php

declare(strict_types=1);

namespace App\Components\Payment\BankAccountDetail;

use App\Components\DataGrid;
use App\Components\Grids\GridFactory;
use App\Components\Payment\BankPairingUiMessages;
use App\Model\Bank\Entity\BankTransaction;
use App\Model\Bank\Entity\BankTransactionPairing;
use App\Model\Bank\Exception\BankTimeLimit;
use App\Model\Bank\Exception\BankTimeout;
use App\Model\Bank\Exception\BankWrongTokenAccount;
use App\Model\Bank\PairingCandidate;
use App\Model\Bank\Repository\BankTransactionPairingRepository;
use App\Model\Bank\Services\BankPairingCandidateProvider;
use App\Model\Invoice\Entity\Invoice;
use App\Model\Invoice\Repository\InvoiceRepository;
use App\Model\Payment\BankAccountService;
use App\Model\Payment\Payment;
use App\Model\Payment\PaymentNotFound;
use App\Model\Payment\Repositories\IGroupRepository;
use App\Model\Payment\Repositories\IPaymentRepository;
use App\Model\Payment\TokenNotSet;
use Nette\Application\LinkGenerator;
use Nette\Utils\Html;

use function array_filter;
use function array_keys;
use function array_map;
use function array_values;
use function count;
use function implode;
use function in_array;
use function number_format;
use function sprintf;

final class BankAccountDetailViewFactory
{
    private const DAYS_BACK = 60;

    public function __construct(
        private readonly BankAccountService $accounts,
        private readonly BankPairingCandidateProvider $pairingCandidates,
        private readonly BankTransactionPairingRepository $pairings,
        private readonly IPaymentRepository $payments,
        private readonly InvoiceRepository $invoices,
        private readonly IGroupRepository $groups,
        private readonly LinkGenerator $linkGenerator,
        private readonly GridFactory $gridFactory,
    ) {
    }

    /**
     * @param array<int, string> $groupNames
     * @param int[]              $readableUnitIds
     */
    public function create(
        int $accountId,
        array $groupNames,
        array $readableUnitIds,
        ?int $paymentId = null,
        ?int $invoiceId = null,
        bool $includeInvoices = true,
        ?string $transactionView = null,
    ): BankAccountDetail {
        $accessibleGroupIds = array_keys($groupNames);
        $invoiceUnitIds = $includeInvoices ? $readableUnitIds : [];
        $focusTarget = $this->resolveFocusTarget(
            $accountId,
            $paymentId,
            $includeInvoices ? $invoiceId : null,
            $invoiceUnitIds,
            $accessibleGroupIds,
        );

        try {
            $transactions = $this->accounts->getPersistentTransactions($accountId, self::DAYS_BACK);
            $domainCandidates = $this->pairingCandidates->getDomainCandidatesForBankAccount($accountId);
            if (! $includeInvoices) {
                $domainCandidates = array_values(array_filter(
                    $domainCandidates,
                    static fn (PairingCandidate $candidate): bool => $candidate->getInvoice() === null,
                ));
            }

            $activePairings = $this->pairings->findActiveByTransactionKeys(
                array_map(static fn (BankTransaction $transaction): string => $transaction->getTransactionKey(), $transactions),
            );

            return new BankAccountDetail(
                $this->buildTransactionRows(
                    $transactions,
                    $domainCandidates,
                    $activePairings,
                    $groupNames,
                    $invoiceUnitIds,
                    $focusTarget?->targetKey,
                    $includeInvoices,
                    $transactionView,
                ),
                $this->accounts->getImportBatches($accountId),
                $focusTarget?->label,
            );
        } catch (TokenNotSet) {
            return new BankAccountDetail(null, [], $focusTarget?->label, 'Nemáte vyplněný token pro komunikaci s FIO');
        } catch (BankTimeLimit) {
            return new BankAccountDetail(null, [], $focusTarget?->label, BankPairingUiMessages::TIME_LIMIT_MESSAGE);
        } catch (BankTimeout) {
            return new BankAccountDetail(null, [], $focusTarget?->label, null, BankPairingUiMessages::TIMEOUT_MESSAGE);
        } catch (BankWrongTokenAccount $exception) {
            return new BankAccountDetail(null, [], $focusTarget?->label, null, BankPairingUiMessages::wrongTokenAccountMessage($exception));
        }
    }

    public function createForPaymentGroup(int $accountId, int $groupId, string $groupName, ?int $paymentId = null): BankAccountDetail
    {
        $focusTarget = $this->resolveFocusTarget($accountId, $paymentId, null, [], [$groupId]);

        try {
            $transactions = $this->accounts->getPersistentTransactions($accountId, self::DAYS_BACK);
            $domainCandidates = array_values(array_filter(
                $this->pairingCandidates->getDomainCandidatesForBankAccount($accountId),
                static fn (PairingCandidate $candidate): bool => $candidate->getPayment() instanceof Payment,
            ));
            $activePairings = $this->pairings->findActiveByTransactionKeys(
                array_map(static fn (BankTransaction $transaction): string => $transaction->getTransactionKey(), $transactions),
            );

            return new BankAccountDetail(
                $this->buildTransactionRows(
                    $transactions,
                    $domainCandidates,
                    $activePairings,
                    [$groupId => $groupName],
                    [],
                    $focusTarget?->targetKey,
                    false,
                    null,
                    $groupId,
                ),
                [],
                $focusTarget?->label,
            );
        } catch (TokenNotSet) {
            return new BankAccountDetail(null, [], $focusTarget?->label, warningMessage: 'Nemáte vyplněný token pro komunikaci s FIO');
        } catch (BankTimeLimit) {
            return new BankAccountDetail(null, [], $focusTarget?->label, warningMessage: BankPairingUiMessages::TIME_LIMIT_MESSAGE);
        } catch (BankTimeout) {
            return new BankAccountDetail(null, [], $focusTarget?->label, null, BankPairingUiMessages::TIMEOUT_MESSAGE);
        } catch (BankWrongTokenAccount $exception) {
            return new BankAccountDetail(null, [], $focusTarget?->label, null, BankPairingUiMessages::wrongTokenAccountMessage($exception));
        }
    }

    /**
     * @param list<BankAccountTransactionRow> $transactionRows
     */
    public function createTransactionsGrid(array $transactionRows, bool $incomingOnly, bool $paginate): DataGrid
    {
        $grid = $paginate
            ? $this->gridFactory->create(__DIR__.'/templates/BankAccountTransactionsGrid.latte')
            : $this->gridFactory->createSimpleGrid();
        if ($paginate) {
            $grid->setOuterFilterRendering(true);
            $grid->setCollapsibleOuterFilters(false);
            $grid->setRememberState(false);
            $grid->setColumnReset(false);
        }

        $grid->setPrimaryKey('transactionKey');

        $grid->addColumnDateTime('date', 'Datum')
            ->setFormat('j.n. Y')
            ->setSortable();

        $grid->addColumnText('amount', 'Částka')
            ->setRenderer(fn (array $row): Html => $this->renderTransactionAmount((float) $row['amount']))
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
            ->setRenderer(fn (array $row): Html => $this->renderTransactionStatus($row['row']));

        $grid->addFilterText('search', '', ['counterAccount', 'counterName', 'constantSymbol', 'variableSymbol', 'note', 'statusSearch'])
            ->setPlaceholder('Hledat transakci...');

        $grid->setDefaultSort(['date' => DataGrid::SORT_DESC]);
        $grid->setDataSource($this->buildTransactionGridRows($transactionRows, $incomingOnly));

        return $grid;
    }

    /**
     * @param  list<BankTransaction>           $transactions
     * @param  list<PairingCandidate>          $domainCandidates
     * @param  list<BankTransactionPairing>    $activePairings
     * @param  array<int, string>              $groupNames
     * @param  int[]                           $readableUnitIds
     * @return list<BankAccountTransactionRow>
     */
    private function buildTransactionRows(
        array $transactions,
        array $domainCandidates,
        array $activePairings,
        array $groupNames,
        array $readableUnitIds,
        ?string $focusTargetKey,
        bool $includeInvoices,
        ?string $transactionView,
        ?int $manualPairingGroupId = null,
    ): array {
        $pairingsByTransactionKey = [];
        foreach ($activePairings as $pairing) {
            $pairingsByTransactionKey[$pairing->getTransactionKey()] = $pairing;
        }

        $candidatesByMatchKey = [];
        $candidatesByVariableSymbol = [];
        foreach ($domainCandidates as $candidate) {
            $candidatesByMatchKey[$candidate->getMatchKey()][] = $candidate;

            $variableSymbol = $candidate->getPayment()?->getVariableSymbol()?->toInt()
                ?? $candidate->getInvoice()?->getVariableSymbol()->toInt();

            if ($variableSymbol !== null) {
                $candidatesByVariableSymbol[$variableSymbol][] = $candidate;
            }
        }

        $accessibleGroupIds = array_keys($groupNames);
        $accessibleGroups = [];
        foreach ($this->groups->findByIds($accessibleGroupIds) as $group) {
            $accessibleGroups[$group->getId()] = $group;
        }

        $accessiblePayments = array_filter(
            $this->payments->findByMultipleGroups($accessibleGroupIds),
            static fn (Payment $payment): bool => $payment->canBePaired(),
        );
        $accessibleInvoices = $includeInvoices ? $this->invoices->findOpenTransferInvoicesByUnits($readableUnitIds) : [];
        $rows = [];

        foreach ($transactions as $transaction) {
            $pairing = $pairingsByTransactionKey[$transaction->getTransactionKey()] ?? null;
            $matchKey = $this->getTransactionMatchKey($transaction);
            $allExactCandidates = $matchKey !== null ? ($candidatesByMatchKey[$matchKey] ?? []) : [];
            $allVariableSymbolCandidates = $transaction->getVariableSymbol() !== null
                ? ($candidatesByVariableSymbol[$transaction->getVariableSymbol()] ?? [])
                : [];

            $visibleExactCandidates = $this->describeVisibleCandidates($allExactCandidates, $accessibleGroupIds, $groupNames, $readableUnitIds);
            $visibleVariableSymbolCandidates = $this->describeVisibleCandidates($allVariableSymbolCandidates, $accessibleGroupIds, $groupNames, $readableUnitIds);
            $pairingLabel = $pairing !== null
                ? $this->describePairing($pairing, $accessibleGroupIds, $groupNames, $readableUnitIds, $includeInvoices)
                : null;
            $manualCandidates = $pairing === null
                ? $this->buildManualCandidates(
                    $transaction,
                    $accessiblePayments,
                    $accessibleInvoices,
                    $accessibleGroups,
                    $groupNames,
                    $transactionView,
                    $manualPairingGroupId,
                )
                : [];

            $rows[] = new BankAccountTransactionRow(
                $transaction,
                $pairing,
                $pairingLabel,
                $manualCandidates,
                $visibleExactCandidates,
                $visibleVariableSymbolCandidates,
                $this->resolveConflictReason(
                    $transaction,
                    $pairing,
                    $allExactCandidates,
                    $allVariableSymbolCandidates,
                    $visibleExactCandidates,
                    $manualCandidates,
                    $manualPairingGroupId !== null,
                ),
                $focusTargetKey !== null && (
                    ($pairingLabel?->targetKey === $focusTargetKey)
                    || $this->containsTargetKey($manualCandidates, $focusTargetKey)
                    || $this->containsTargetKey($visibleExactCandidates, $focusTargetKey)
                    || $this->containsTargetKey($visibleVariableSymbolCandidates, $focusTargetKey)
                ),
            );
        }

        if ($focusTargetKey === null) {
            return $rows;
        }

        $focusedRows = array_values(array_filter($rows, static fn (BankAccountTransactionRow $row): bool => $row->isFocusMatch));

        return $focusedRows !== [] ? $focusedRows : $rows;
    }

    private function getTransactionMatchKey(BankTransaction $transaction): ?string
    {
        if ($transaction->getVariableSymbol() === null) {
            return null;
        }

        return $transaction->getVariableSymbol().'|'.number_format($transaction->getAmount(), 2, '.', '');
    }

    /**
     * @param  list<PairingCandidate>           $candidates
     * @param  int[]                            $accessibleGroupIds
     * @param  array<int, string>               $groupNames
     * @param  int[]                            $readableUnitIds
     * @return list<BankAccountTransactionLink>
     */
    private function describeVisibleCandidates(array $candidates, array $accessibleGroupIds, array $groupNames, array $readableUnitIds): array
    {
        $descriptions = [];

        foreach ($candidates as $candidate) {
            $payment = $candidate->getPayment();
            if ($payment instanceof Payment) {
                if (! in_array($payment->getGroupId(), $accessibleGroupIds, true)) {
                    continue;
                }

                $descriptions[] = new BankAccountTransactionLink(
                    'payment',
                    sprintf(
                        'Platba %s%s',
                        $payment->getName(),
                        isset($groupNames[$payment->getGroupId()]) ? ' ve skupině '.$groupNames[$payment->getGroupId()] : '',
                    ),
                    $this->linkGenerator->link('Payments:Payment:default', ['id' => $payment->getGroupId()]).'#payment-'.$payment->getId(),
                    'payment:'.$payment->getId(),
                );
                continue;
            }

            $invoice = $candidate->getInvoice();
            if (! $invoice instanceof Invoice || ! in_array($invoice->getSequence()->getUnit(), $readableUnitIds, true)) {
                continue;
            }

            $descriptions[] = new BankAccountTransactionLink(
                'invoice',
                sprintf('Faktura %s', $invoice->getInvoiceNumber()),
                $this->linkGenerator->link('Payments:InvoiceList:detail', ['id' => $invoice->getId()]),
                'invoice:'.$invoice->getId(),
            );
        }

        return $descriptions;
    }

    /**
     * @param  list<Payment>                        $payments
     * @param  list<Invoice>                        $invoices
     * @param  array<int, \App\Model\Payment\Group> $groups
     * @param  array<int, string>                   $groupNames
     * @return list<BankAccountManualCandidate>
     */
    private function buildManualCandidates(
        BankTransaction $transaction,
        array $payments,
        array $invoices,
        array $groups,
        array $groupNames,
        ?string $transactionView,
        ?int $manualPairingGroupId,
    ): array {
        $transactionAmount = number_format($transaction->getAmount(), 2, '.', '');
        $descriptions = [];

        foreach ($payments as $payment) {
            if (number_format($payment->getAmount(), 2, '.', '') !== $transactionAmount) {
                continue;
            }

            $group = $groups[$payment->getGroupId()] ?? null;
            $warnings = [];

            if ($payment->getVariableSymbol()?->toInt() !== $transaction->getVariableSymbol()) {
                $warnings[] = 'VS se liší.';
            }

            if ($group !== null && $group->getBankAccountId() !== null && $group->getBankAccountId() !== $transaction->getBankAccount()->getId()) {
                $warnings[] = 'Účet se liší.';
            }

            $descriptions[] = new BankAccountManualCandidate(
                'payment',
                sprintf(
                    'Platba %s%s',
                    $payment->getName(),
                    isset($groupNames[$payment->getGroupId()]) ? ' ve skupině '.$groupNames[$payment->getGroupId()] : '',
                ),
                $this->linkGenerator->link('Payments:Payment:default', ['id' => $payment->getGroupId()]).'#payment-'.$payment->getId(),
                $this->linkGenerator->link(
                    $manualPairingGroupId === null
                        ? 'Settings:BankAccounts:pairTransactionToPayment'
                        : 'Payments:Payment:pairTransactionToPayment',
                    [
                        'id' => $manualPairingGroupId,
                        'accountId' => $transaction->getBankAccount()->getId(),
                        'transactionKey' => $transaction->getTransactionKey(),
                        'paymentId' => $payment->getId(),
                        'transactionView' => $transactionView,
                    ],
                ),
                $warnings,
                'payment:'.$payment->getId(),
            );
        }

        foreach ($invoices as $invoice) {
            if (number_format((float) (string) $invoice->getTotalAmount(), 2, '.', '') !== $transactionAmount) {
                continue;
            }

            $warnings = [];

            if ($invoice->getVariableSymbol()->toInt() !== $transaction->getVariableSymbol()) {
                $warnings[] = 'VS se liší.';
            }

            if ($invoice->getBankAccount() !== null && $invoice->getBankAccount()->getId() !== $transaction->getBankAccount()->getId()) {
                $warnings[] = 'Účet se liší.';
            }

            $descriptions[] = new BankAccountManualCandidate(
                'invoice',
                sprintf('Faktura %s', $invoice->getInvoiceNumber()),
                $this->linkGenerator->link('Payments:InvoiceList:detail', ['id' => $invoice->getId()]),
                $this->linkGenerator->link('Settings:BankAccounts:pairTransactionToInvoice', [
                    'accountId' => $transaction->getBankAccount()->getId(),
                    'transactionKey' => $transaction->getTransactionKey(),
                    'invoiceId' => $invoice->getId(),
                    'transactionView' => $transactionView,
                ]),
                $warnings,
                'invoice:'.$invoice->getId(),
            );
        }

        return $descriptions;
    }

    /**
     * @param int[]              $accessibleGroupIds
     * @param array<int, string> $groupNames
     * @param int[]              $readableUnitIds
     */
    private function describePairing(
        BankTransactionPairing $pairing,
        array $accessibleGroupIds,
        array $groupNames,
        array $readableUnitIds,
        bool $includeInvoices,
    ): BankAccountTransactionLink {
        $payment = $pairing->getPayment();
        if ($payment instanceof Payment) {
            if (! in_array($payment->getGroupId(), $accessibleGroupIds, true)) {
                return new BankAccountTransactionLink('payment', 'Spárováno s nepřístupnou platbou', null, 'payment:'.$payment->getId());
            }

            return new BankAccountTransactionLink(
                'payment',
                sprintf(
                    'Spárováno s platbou %s%s',
                    $payment->getName(),
                    isset($groupNames[$payment->getGroupId()]) ? ' ve skupině '.$groupNames[$payment->getGroupId()] : '',
                ),
                $this->linkGenerator->link('Payments:Payment:default', ['id' => $payment->getGroupId()]).'#payment-'.$payment->getId(),
                'payment:'.$payment->getId(),
            );
        }

        $invoice = $pairing->getInvoice();
        if ($invoice instanceof Invoice) {
            if (! $includeInvoices) {
                return new BankAccountTransactionLink('unknown', 'Spárováno s položkou v předběžném přístupu', null, 'history');
            }

            if (! in_array($invoice->getSequence()->getUnit(), $readableUnitIds, true)) {
                return new BankAccountTransactionLink('invoice', 'Spárováno s nepřístupnou fakturou', null, 'invoice:'.$invoice->getId());
            }

            return new BankAccountTransactionLink(
                'invoice',
                sprintf('Spárováno s fakturou %s', $invoice->getInvoiceNumber()),
                $this->linkGenerator->link('Payments:InvoiceList:detail', ['id' => $invoice->getId()]),
                'invoice:'.$invoice->getId(),
            );
        }

        return new BankAccountTransactionLink('unknown', 'Spárováno s historickou položkou', null, 'history');
    }

    /**
     * @param list<PairingCandidate>           $allExactCandidates
     * @param list<PairingCandidate>           $allVariableSymbolCandidates
     * @param list<BankAccountTransactionLink> $visibleExactCandidates
     * @param list<BankAccountManualCandidate> $manualCandidates
     */
    private function resolveConflictReason(
        BankTransaction $transaction,
        ?BankTransactionPairing $pairing,
        array $allExactCandidates,
        array $allVariableSymbolCandidates,
        array $visibleExactCandidates,
        array $manualCandidates,
        bool $isPaymentGroupScope,
    ): ?string {
        if ($pairing !== null) {
            return null;
        }

        if ($transaction->getVariableSymbol() === null) {
            return $manualCandidates === []
                ? 'Transakce nemá variabilní symbol a podle částky nebyla nalezena žádná dostupná nespárovaná položka.'
                : null;
        }

        if (count($allExactCandidates) > 1) {
            if ($isPaymentGroupScope && $this->matchesMultiplePaymentGroups($allExactCandidates)) {
                return 'Nespárovaná platba odpovídá více platebním skupinám se stejným VS a částkou.';
            }

            return 'Existuje více otevřených položek se stejným VS a částkou.';
        }

        if (count($allExactCandidates) === 1 && $visibleExactCandidates === []) {
            return 'Odpovídající položka existuje mimo aktuálně dostupnou část účtu.';
        }

        if (count($allExactCandidates) === 0 && count($allVariableSymbolCandidates) > 1) {
            return 'VS odpovídá více otevřeným položkám, ale částka nesedí nebo není jednoznačná.';
        }

        if (count($allExactCandidates) === 0 && count($allVariableSymbolCandidates) === 1) {
            return 'VS odpovídá otevřené položce, ale částka nesedí.';
        }

        return null;
    }

    /** @param list<PairingCandidate> $candidates */
    private function matchesMultiplePaymentGroups(array $candidates): bool
    {
        $groupIds = [];
        foreach ($candidates as $candidate) {
            $payment = $candidate->getPayment();
            if ($payment instanceof Payment) {
                $groupIds[$payment->getGroupId()] = true;
            }
        }

        return count($groupIds) > 1;
    }

    /**
     * @param  list<BankAccountTransactionRow> $transactionRows
     * @return list<array<string, mixed>>
     */
    private function buildTransactionGridRows(array $transactionRows, bool $incomingOnly): array
    {
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
            $transactionRows,
        );

        if (! $incomingOnly) {
            return $rows;
        }

        return array_values(array_filter(
            $rows,
            static fn (array $row): bool => (float) $row['amount'] > 0,
        ));
    }

    private function renderTransactionAmount(float $amount): Html
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

    private function renderTransactionStatus(BankAccountTransactionRow $row): Html
    {
        $container = Html::el('div')
            ->setAttribute('class', 'd-flex flex-column gap-1');

        if ($row->pairingLabel !== null) {
            $pairing = Html::el('div');
            $pairing->addHtml(Html::el('span')->setAttribute('class', 'badge text-bg-info')->setText('Spárováno'));
            $pairing->addText(' ');
            $pairing->addHtml($this->renderTransactionLink($row->pairingLabel));
            $container->addHtml($pairing);
        }

        if ($row->manualCandidates !== []) {
            $container->addHtml(Html::el('div')->setAttribute('class', 'small text-body-secondary')->setText('Ruční párování podle částky:'));

            foreach ($row->manualCandidates as $candidate) {
                $container->addHtml($this->renderManualCandidate($candidate));
            }
        }

        if ($row->exactCandidates !== []) {
            $container->addHtml(Html::el('div')->setAttribute('class', 'small text-body-secondary')->setText('Jednoznačné automatické shody:'));

            foreach ($row->exactCandidates as $candidate) {
                $container->addHtml($this->renderCandidateLink($candidate));
            }
        }

        if ($row->conflictReason !== null) {
            $container->addHtml(Html::el('div')->setAttribute('class', 'small text-danger')->setText($row->conflictReason));
        }

        if ($row->variableSymbolCandidates !== [] && $row->exactCandidates === []) {
            $container->addHtml(Html::el('div')->setAttribute('class', 'small text-body-secondary')->setText('Položky se shodným VS:'));

            foreach ($row->variableSymbolCandidates as $candidate) {
                $container->addHtml($this->renderCandidateLink($candidate));
            }
        }

        return $container;
    }

    private function renderManualCandidate(BankAccountManualCandidate $candidate): Html
    {
        $container = Html::el('div')->setAttribute('class', 'mb-1');
        $container->addHtml($this->renderTypeBadge($candidate->type));
        $container->addText(' ');
        $container->addHtml(Html::el('a')->href($candidate->url)->setText($candidate->label));
        $container->addText(' ');
        $container->addHtml(
            Html::el('a')
                ->href($candidate->actionUrl)
                ->setAttribute('class', 'ajax btn btn-sm btn-outline-success ms-1')
                ->setAttribute('data-confirm', 'Opravdu chceš ručně spárovat tuto bankovní transakci?')
                ->setText('Spárovat'),
        );

        if ($candidate->warnings !== []) {
            $container->addText(' ');
            $container->addHtml(Html::el('span')->setAttribute('class', 'small text-warning ms-1')->setText(implode(', ', $candidate->warnings)));
        }

        return $container;
    }

    private function renderCandidateLink(BankAccountTransactionLink $candidate): Html
    {
        $container = Html::el('div');
        $container->addHtml($this->renderTypeBadge($candidate->type));
        $container->addText(' ');
        $container->addHtml($this->renderTransactionLink($candidate));

        return $container;
    }

    private function renderTransactionLink(BankAccountTransactionLink $link): Html
    {
        if ($link->url === null) {
            return Html::el('span')->setText($link->label);
        }

        return Html::el('a')
            ->href($link->url)
            ->setText($link->label);
    }

    private function renderTypeBadge(string $type): Html
    {
        return Html::el('span')
            ->setAttribute('class', $type === 'invoice' ? 'badge text-bg-secondary' : 'badge text-bg-primary')
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

    /** @param list<object{targetKey: string}> $descriptions */
    private function containsTargetKey(array $descriptions, string $focusTargetKey): bool
    {
        foreach ($descriptions as $description) {
            if ($description->targetKey === $focusTargetKey) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param int[] $readableUnitIds
     * @param int[] $accessibleGroupIds
     */
    private function resolveFocusTarget(
        int $accountId,
        ?int $paymentId,
        ?int $invoiceId,
        array $readableUnitIds,
        array $accessibleGroupIds,
    ): ?BankAccountFocusTarget {
        if ($paymentId !== null) {
            try {
                $payment = $this->payments->find($paymentId);
            } catch (PaymentNotFound) {
                return null;
            }

            if (! in_array($payment->getGroupId(), $accessibleGroupIds, true)) {
                return null;
            }

            $group = $this->groups->find($payment->getGroupId());
            if ($group->getBankAccountId() !== $accountId) {
                return null;
            }

            return new BankAccountFocusTarget(
                'payment:'.$payment->getId(),
                'Zobrazené transakce relevantní pro platbu '.$payment->getName().'.',
            );
        }

        if ($invoiceId !== null) {
            $invoice = $this->invoices->findAccessibleByUnits($invoiceId, $readableUnitIds);
            if (! $invoice instanceof Invoice || $invoice->getBankAccount()?->getId() !== $accountId) {
                return null;
            }

            return new BankAccountFocusTarget(
                'invoice:'.$invoice->getId(),
                'Zobrazené transakce relevantní pro fakturu '.$invoice->getInvoiceNumber().'.',
            );
        }

        return null;
    }
}
