<?php

declare(strict_types=1);

namespace App\Model\DTO\Stat;

use DateTimeImmutable;
use Nette\SmartObject;

/**
 * @property int                    $events
 * @property int                    $camps
 * @property int                    $paymentGroups
 * @property int                    $eventTotal
 * @property int                    $eventDraft
 * @property int                    $eventClosed
 * @property int                    $eventCancelled
 * @property int                    $eventWithExpense
 * @property int                    $eventWithoutExpense
 * @property int                    $campTotal
 * @property int                    $campDraft
 * @property int                    $campApprovedParent
 * @property int                    $campApprovedLeader
 * @property int                    $campReal
 * @property int                    $campWithExpense
 * @property int                    $campWithoutExpense
 * @property int                    $campWithParticipantStats
 * @property int                    $paymentGroupsOpen
 * @property int                    $paymentGroupsClosed
 * @property int                    $paymentsTotal
 * @property int                    $paymentsPreparing
 * @property int                    $paymentsCompleted
 * @property int                    $paymentsCanceled
 * @property float                  $paymentsAmountTotal
 * @property float                  $paymentsAmountCompleted
 * @property int                    $paymentsAutomaticPairings
 * @property int                    $invoicesTotal
 * @property int                    $invoicesIssued
 * @property int                    $invoicesDelivered
 * @property int                    $invoicesPaid
 * @property int                    $invoicesCancelled
 * @property int                    $invoicesSent
 * @property float                  $invoicesAmountTotal
 * @property float                  $invoicesAmountPaid
 * @property int                    $bankAccountsTotal
 * @property int                    $bankAccountsFio
 * @property int                    $bankAccountsGpc
 * @property int                    $bankTransactionsTotal
 * @property int                    $bankTransactionsUnpaired
 * @property DateTimeImmutable|null $bankLastImportAt
 * @property int                    $bugReportsTotal
 * @property int                    $bugReportsOpen
 * @property int                    $bugReportsFixed
 * @property int                    $bugReportsRejected
 * @property float|null             $bugReportAverageResolutionHours
 */
final class Counter
{
    use SmartObject;

    private int $eventTotal = 0;
    private int $eventDraft = 0;
    private int $eventClosed = 0;
    private int $eventCancelled = 0;
    private int $eventWithExpense = 0;
    private int $eventWithoutExpense = 0;
    private int $campTotal = 0;
    private int $campDraft = 0;
    private int $campApprovedParent = 0;
    private int $campApprovedLeader = 0;
    private int $campReal = 0;
    private int $campWithExpense = 0;
    private int $campWithoutExpense = 0;
    private int $campWithParticipantStats = 0;
    private int $paymentGroupsOpen = 0;
    private int $paymentGroupsClosed = 0;
    private int $paymentsTotal = 0;
    private int $paymentsPreparing = 0;
    private int $paymentsCompleted = 0;
    private int $paymentsCanceled = 0;
    private float $paymentsAmountTotal = 0.0;
    private float $paymentsAmountCompleted = 0.0;
    private int $paymentsAutomaticPairings = 0;
    private int $invoicesTotal = 0;
    private int $invoicesIssued = 0;
    private int $invoicesDelivered = 0;
    private int $invoicesPaid = 0;
    private int $invoicesCancelled = 0;
    private int $invoicesSent = 0;
    private float $invoicesAmountTotal = 0.0;
    private float $invoicesAmountPaid = 0.0;
    private int $bankAccountsTotal = 0;
    private int $bankAccountsFio = 0;
    private int $bankAccountsGpc = 0;
    private int $bankTransactionsTotal = 0;
    private int $bankTransactionsUnpaired = 0;
    private ?DateTimeImmutable $bankLastImportAt = null;
    private int $bugReportsTotal = 0;
    private int $bugReportsOpen = 0;
    private int $bugReportsFixed = 0;
    private int $bugReportsRejected = 0;
    private int $bugReportResolvedCount = 0;
    private int $bugReportResolutionSeconds = 0;

    public function __construct(private int $events = 0, private int $camps = 0, private int $paymentGroups = 0)
    {
    }

    public function addEvent(string $state, bool $withExpense): void
    {
        ++$this->eventTotal;
        match ($state) {
            'draft' => ++$this->eventDraft,
            'closed' => ++$this->eventClosed,
            'cancelled' => ++$this->eventCancelled,
            default => null,
        };

        if ($withExpense) {
            ++$this->eventWithExpense;

            return;
        }

        ++$this->eventWithoutExpense;
    }

    public function addCamp(string $state, bool $withExpense, bool $withParticipantStats): void
    {
        ++$this->campTotal;
        match ($state) {
            'draft' => ++$this->campDraft,
            'approvedParent' => ++$this->campApprovedParent,
            'approvedLeader' => ++$this->campApprovedLeader,
            'real' => ++$this->campReal,
            default => null,
        };

        if ($withExpense) {
            ++$this->campWithExpense;
        } else {
            ++$this->campWithoutExpense;
        }

        if ($withParticipantStats) {
            ++$this->campWithParticipantStats;
        }
    }

    public function addPaymentStats(
        int $groupsOpen,
        int $groupsClosed,
        int $paymentsTotal,
        int $paymentsPreparing,
        int $paymentsCompleted,
        int $paymentsCanceled,
        float $amountTotal,
        float $amountCompleted,
        int $automaticPairings,
    ): void {
        $this->paymentGroupsOpen += $groupsOpen;
        $this->paymentGroupsClosed += $groupsClosed;
        $this->paymentGroups += $groupsOpen + $groupsClosed;
        $this->paymentsTotal += $paymentsTotal;
        $this->paymentsPreparing += $paymentsPreparing;
        $this->paymentsCompleted += $paymentsCompleted;
        $this->paymentsCanceled += $paymentsCanceled;
        $this->paymentsAmountTotal += $amountTotal;
        $this->paymentsAmountCompleted += $amountCompleted;
        $this->paymentsAutomaticPairings += $automaticPairings;
    }

    public function addInvoiceStats(
        int $total,
        int $issued,
        int $delivered,
        int $paid,
        int $cancelled,
        int $sent,
        float $amountTotal,
        float $amountPaid,
    ): void {
        $this->invoicesTotal += $total;
        $this->invoicesIssued += $issued;
        $this->invoicesDelivered += $delivered;
        $this->invoicesPaid += $paid;
        $this->invoicesCancelled += $cancelled;
        $this->invoicesSent += $sent;
        $this->invoicesAmountTotal += $amountTotal;
        $this->invoicesAmountPaid += $amountPaid;
    }

    public function addBankStats(
        int $accountsTotal,
        int $accountsFio,
        int $accountsGpc,
        int $transactionsTotal,
        int $transactionsUnpaired,
        ?DateTimeImmutable $lastImportAt,
    ): void {
        $this->bankAccountsTotal += $accountsTotal;
        $this->bankAccountsFio += $accountsFio;
        $this->bankAccountsGpc += $accountsGpc;
        $this->bankTransactionsTotal += $transactionsTotal;
        $this->bankTransactionsUnpaired += $transactionsUnpaired;

        if ($lastImportAt !== null && ($this->bankLastImportAt === null || $lastImportAt > $this->bankLastImportAt)) {
            $this->bankLastImportAt = $lastImportAt;
        }
    }

    public function addBugReportStats(
        int $total,
        int $open,
        int $fixed,
        int $rejected,
        int $resolvedCount,
        int $resolutionSeconds,
    ): void {
        $this->bugReportsTotal += $total;
        $this->bugReportsOpen += $open;
        $this->bugReportsFixed += $fixed;
        $this->bugReportsRejected += $rejected;
        $this->bugReportResolvedCount += $resolvedCount;
        $this->bugReportResolutionSeconds += $resolutionSeconds;
    }

    public function getEvents(): int
    {
        return $this->events;
    }

    public function getCamps(): int
    {
        return $this->camps;
    }

    public function getPaymentGroups(): int
    {
        return $this->paymentGroups;
    }

    public function isEmpty(): bool
    {
        return $this->events === 0
            && $this->camps === 0
            && $this->paymentGroups === 0
            && $this->eventTotal === 0
            && $this->campTotal === 0
            && $this->paymentsTotal === 0
            && $this->invoicesTotal === 0
            && $this->bankAccountsTotal === 0
            && $this->bankTransactionsTotal === 0
            && $this->bugReportsTotal === 0;
    }

    public function takeIn(Counter $counter): void
    {
        $this->events += $counter->getEvents();
        $this->camps += $counter->getCamps();
        $this->paymentGroups += $counter->getPaymentGroups();
        $this->eventTotal += $counter->getEventTotal();
        $this->eventDraft += $counter->getEventDraft();
        $this->eventClosed += $counter->getEventClosed();
        $this->eventCancelled += $counter->getEventCancelled();
        $this->eventWithExpense += $counter->getEventWithExpense();
        $this->eventWithoutExpense += $counter->getEventWithoutExpense();
        $this->campTotal += $counter->getCampTotal();
        $this->campDraft += $counter->getCampDraft();
        $this->campApprovedParent += $counter->getCampApprovedParent();
        $this->campApprovedLeader += $counter->getCampApprovedLeader();
        $this->campReal += $counter->getCampReal();
        $this->campWithExpense += $counter->getCampWithExpense();
        $this->campWithoutExpense += $counter->getCampWithoutExpense();
        $this->campWithParticipantStats += $counter->getCampWithParticipantStats();
        $this->paymentGroupsOpen += $counter->getPaymentGroupsOpen();
        $this->paymentGroupsClosed += $counter->getPaymentGroupsClosed();
        $this->paymentsTotal += $counter->getPaymentsTotal();
        $this->paymentsPreparing += $counter->getPaymentsPreparing();
        $this->paymentsCompleted += $counter->getPaymentsCompleted();
        $this->paymentsCanceled += $counter->getPaymentsCanceled();
        $this->paymentsAmountTotal += $counter->getPaymentsAmountTotal();
        $this->paymentsAmountCompleted += $counter->getPaymentsAmountCompleted();
        $this->paymentsAutomaticPairings += $counter->getPaymentsAutomaticPairings();
        $this->invoicesTotal += $counter->getInvoicesTotal();
        $this->invoicesIssued += $counter->getInvoicesIssued();
        $this->invoicesDelivered += $counter->getInvoicesDelivered();
        $this->invoicesPaid += $counter->getInvoicesPaid();
        $this->invoicesCancelled += $counter->getInvoicesCancelled();
        $this->invoicesSent += $counter->getInvoicesSent();
        $this->invoicesAmountTotal += $counter->getInvoicesAmountTotal();
        $this->invoicesAmountPaid += $counter->getInvoicesAmountPaid();
        $this->bankAccountsTotal += $counter->getBankAccountsTotal();
        $this->bankAccountsFio += $counter->getBankAccountsFio();
        $this->bankAccountsGpc += $counter->getBankAccountsGpc();
        $this->bankTransactionsTotal += $counter->getBankTransactionsTotal();
        $this->bankTransactionsUnpaired += $counter->getBankTransactionsUnpaired();
        $lastImportAt = $counter->getBankLastImportAt();
        if ($lastImportAt !== null && ($this->bankLastImportAt === null || $lastImportAt > $this->bankLastImportAt)) {
            $this->bankLastImportAt = $lastImportAt;
        }

        $this->bugReportsTotal += $counter->getBugReportsTotal();
        $this->bugReportsOpen += $counter->getBugReportsOpen();
        $this->bugReportsFixed += $counter->getBugReportsFixed();
        $this->bugReportsRejected += $counter->getBugReportsRejected();
        $this->bugReportResolvedCount += $counter->bugReportResolvedCount;
        $this->bugReportResolutionSeconds += $counter->bugReportResolutionSeconds;
    }

    public function getEventTotal(): int
    {
        return $this->eventTotal;
    }

    public function getEventDraft(): int
    {
        return $this->eventDraft;
    }

    public function getEventClosed(): int
    {
        return $this->eventClosed;
    }

    public function getEventCancelled(): int
    {
        return $this->eventCancelled;
    }

    public function getEventWithExpense(): int
    {
        return $this->eventWithExpense;
    }

    public function getEventWithoutExpense(): int
    {
        return $this->eventWithoutExpense;
    }

    public function getCampTotal(): int
    {
        return $this->campTotal;
    }

    public function getCampDraft(): int
    {
        return $this->campDraft;
    }

    public function getCampApprovedParent(): int
    {
        return $this->campApprovedParent;
    }

    public function getCampApprovedLeader(): int
    {
        return $this->campApprovedLeader;
    }

    public function getCampReal(): int
    {
        return $this->campReal;
    }

    public function getCampWithExpense(): int
    {
        return $this->campWithExpense;
    }

    public function getCampWithoutExpense(): int
    {
        return $this->campWithoutExpense;
    }

    public function getCampWithParticipantStats(): int
    {
        return $this->campWithParticipantStats;
    }

    public function getPaymentGroupsOpen(): int
    {
        return $this->paymentGroupsOpen;
    }

    public function getPaymentGroupsClosed(): int
    {
        return $this->paymentGroupsClosed;
    }

    public function getPaymentsTotal(): int
    {
        return $this->paymentsTotal;
    }

    public function getPaymentsPreparing(): int
    {
        return $this->paymentsPreparing;
    }

    public function getPaymentsCompleted(): int
    {
        return $this->paymentsCompleted;
    }

    public function getPaymentsCanceled(): int
    {
        return $this->paymentsCanceled;
    }

    public function getPaymentsAmountTotal(): float
    {
        return $this->paymentsAmountTotal;
    }

    public function getPaymentsAmountCompleted(): float
    {
        return $this->paymentsAmountCompleted;
    }

    public function getPaymentsAutomaticPairings(): int
    {
        return $this->paymentsAutomaticPairings;
    }

    public function getInvoicesTotal(): int
    {
        return $this->invoicesTotal;
    }

    public function getInvoicesIssued(): int
    {
        return $this->invoicesIssued;
    }

    public function getInvoicesDelivered(): int
    {
        return $this->invoicesDelivered;
    }

    public function getInvoicesPaid(): int
    {
        return $this->invoicesPaid;
    }

    public function getInvoicesCancelled(): int
    {
        return $this->invoicesCancelled;
    }

    public function getInvoicesSent(): int
    {
        return $this->invoicesSent;
    }

    public function getInvoicesAmountTotal(): float
    {
        return $this->invoicesAmountTotal;
    }

    public function getInvoicesAmountPaid(): float
    {
        return $this->invoicesAmountPaid;
    }

    public function getBankAccountsTotal(): int
    {
        return $this->bankAccountsTotal;
    }

    public function getBankAccountsFio(): int
    {
        return $this->bankAccountsFio;
    }

    public function getBankAccountsGpc(): int
    {
        return $this->bankAccountsGpc;
    }

    public function getBankTransactionsTotal(): int
    {
        return $this->bankTransactionsTotal;
    }

    public function getBankTransactionsUnpaired(): int
    {
        return $this->bankTransactionsUnpaired;
    }

    public function getBankLastImportAt(): ?DateTimeImmutable
    {
        return $this->bankLastImportAt;
    }

    public function getBugReportsTotal(): int
    {
        return $this->bugReportsTotal;
    }

    public function getBugReportsOpen(): int
    {
        return $this->bugReportsOpen;
    }

    public function getBugReportsFixed(): int
    {
        return $this->bugReportsFixed;
    }

    public function getBugReportsRejected(): int
    {
        return $this->bugReportsRejected;
    }

    public function getBugReportAverageResolutionHours(): ?float
    {
        if ($this->bugReportResolvedCount === 0) {
            return null;
        }

        return $this->bugReportResolutionSeconds / $this->bugReportResolvedCount / 3600;
    }
}
