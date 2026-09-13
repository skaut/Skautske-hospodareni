<?php

declare(strict_types=1);

namespace App\Model\DTO\Stat;

use Codeception\Test\Unit;
use DateTimeImmutable;

/**
 * Sčítačka statistik jednotky — doplňuje CounterTest o platby, faktury, banku a hlášení chyb.
 */
final class CounterStatsTest extends Unit
{
    public function testCampStatesAreCountedByCategory(): void
    {
        $counter = new Counter();

        $counter->addCamp('draft', false, false);
        $counter->addCamp('approvedParent', true, false);
        $counter->addCamp('approvedLeader', true, true);
        $counter->addCamp('real', true, true);
        $counter->addCamp('neznámý', false, false);

        self::assertSame(5, $counter->getCampTotal());
        self::assertSame(1, $counter->getCampDraft());
        self::assertSame(1, $counter->getCampApprovedParent());
        self::assertSame(1, $counter->getCampApprovedLeader());
        self::assertSame(1, $counter->getCampReal());
        self::assertSame(3, $counter->getCampWithExpense());
        self::assertSame(2, $counter->getCampWithoutExpense());
        self::assertSame(2, $counter->getCampWithParticipantStats());
    }

    public function testPaymentStatsAccumulateAcrossCalls(): void
    {
        $counter = new Counter();

        $counter->addPaymentStats(2, 1, 10, 4, 5, 1, 5000.0, 2500.0, 3);
        $counter->addPaymentStats(1, 0, 5, 2, 3, 0, 1000.0, 500.0, 1);

        self::assertSame(3, $counter->getPaymentGroupsOpen());
        self::assertSame(1, $counter->getPaymentGroupsClosed());
        self::assertSame(4, $counter->getPaymentGroups(), 'skupiny se počítají jako otevřené + uzavřené');
        self::assertSame(15, $counter->getPaymentsTotal());
        self::assertSame(6, $counter->getPaymentsPreparing());
        self::assertSame(8, $counter->getPaymentsCompleted());
        self::assertSame(1, $counter->getPaymentsCanceled());
        self::assertSame(6000.0, $counter->getPaymentsAmountTotal());
        self::assertSame(3000.0, $counter->getPaymentsAmountCompleted());
        self::assertSame(4, $counter->getPaymentsAutomaticPairings());
    }

    public function testInvoiceStatsAccumulate(): void
    {
        $counter = new Counter();

        $counter->addInvoiceStats(4, 1, 1, 1, 1, 2, 4000.0, 1000.0);
        $counter->addInvoiceStats(2, 1, 0, 1, 0, 1, 2000.0, 1500.0);

        self::assertSame(6, $counter->getInvoicesTotal());
        self::assertSame(2, $counter->getInvoicesIssued());
        self::assertSame(1, $counter->getInvoicesDelivered());
        self::assertSame(2, $counter->getInvoicesPaid());
        self::assertSame(1, $counter->getInvoicesCancelled());
        self::assertSame(3, $counter->getInvoicesSent());
        self::assertSame(6000.0, $counter->getInvoicesAmountTotal());
        self::assertSame(2500.0, $counter->getInvoicesAmountPaid());
    }

    public function testBankStatsKeepTheNewestImport(): void
    {
        $counter = new Counter();

        $counter->addBankStats(1, 1, 0, 10, 2, new DateTimeImmutable('2026-07-01 08:00:00'));
        $counter->addBankStats(1, 0, 1, 5, 1, new DateTimeImmutable('2026-07-15 08:00:00'));
        $counter->addBankStats(1, 0, 1, 1, 0, new DateTimeImmutable('2026-06-01 08:00:00'));
        $counter->addBankStats(1, 0, 1, 1, 0, null);

        self::assertSame(4, $counter->getBankAccountsTotal());
        self::assertSame(1, $counter->getBankAccountsFio());
        self::assertSame(3, $counter->getBankAccountsGpc());
        self::assertSame(17, $counter->getBankTransactionsTotal());
        self::assertSame(3, $counter->getBankTransactionsUnpaired());
        self::assertSame('2026-07-15 08:00:00', $counter->getBankLastImportAt()?->format('Y-m-d H:i:s'), 'starší import čas nepřepíše');
    }

    public function testBugReportStatsComputeAverageResolutionTime(): void
    {
        $counter = new Counter();

        self::assertNull($counter->getBugReportAverageResolutionHours(), 'bez vyřešených hlášení není co počítat');

        $counter->addBugReportStats(5, 2, 2, 1, 3, 10800);

        self::assertSame(5, $counter->getBugReportsTotal());
        self::assertSame(2, $counter->getBugReportsOpen());
        self::assertSame(2, $counter->getBugReportsFixed());
        self::assertSame(1, $counter->getBugReportsRejected());
        self::assertSame(1.0, $counter->getBugReportAverageResolutionHours(), '3 hlášení za 10800 s = 1 hodina na hlášení');
    }

    public function testTakeInMergesTwoCountersIncludingNewestImport(): void
    {
        $unit = new Counter(1, 1, 1);
        $unit->addEvent('draft', true);
        $unit->addCamp('real', true, true);
        $unit->addPaymentStats(1, 0, 2, 1, 1, 0, 100.0, 50.0, 1);
        $unit->addInvoiceStats(1, 1, 0, 0, 0, 0, 200.0, 0.0);
        $unit->addBankStats(1, 1, 0, 3, 1, new DateTimeImmutable('2026-07-01 08:00:00'));
        $unit->addBugReportStats(2, 1, 1, 0, 1, 3600);

        $troop = new Counter(2, 0, 0);
        $troop->addEvent('closed', false);
        $troop->addPaymentStats(0, 1, 1, 0, 1, 0, 300.0, 300.0, 0);
        $troop->addBankStats(1, 0, 1, 1, 0, new DateTimeImmutable('2026-07-20 08:00:00'));
        $troop->addBugReportStats(1, 0, 0, 1, 1, 7200);

        $unit->takeIn($troop);

        self::assertSame(3, $unit->getEvents());
        self::assertSame(1, $unit->getCamps());
        self::assertSame(2, $unit->getEventTotal());
        self::assertSame(1, $unit->getEventDraft());
        self::assertSame(1, $unit->getEventClosed());
        self::assertSame(3, $unit->getPaymentsTotal());
        self::assertSame(400.0, $unit->getPaymentsAmountTotal());
        self::assertSame(1, $unit->getInvoicesTotal());
        self::assertSame(2, $unit->getBankAccountsTotal());
        self::assertSame(4, $unit->getBankTransactionsTotal());
        self::assertSame('2026-07-20 08:00:00', $unit->getBankLastImportAt()?->format('Y-m-d H:i:s'));
        self::assertSame(3, $unit->getBugReportsTotal());
        self::assertSame(1.5, $unit->getBugReportAverageResolutionHours(), '10800 s na 2 hlášení');
    }

    public function testTakeInFromEmptyCounterKeepsOriginalImportTime(): void
    {
        $counter = new Counter();
        $counter->addBankStats(1, 1, 0, 1, 0, new DateTimeImmutable('2026-07-01 08:00:00'));

        $counter->takeIn(new Counter());

        self::assertSame('2026-07-01 08:00:00', $counter->getBankLastImportAt()?->format('Y-m-d H:i:s'));
    }
}
