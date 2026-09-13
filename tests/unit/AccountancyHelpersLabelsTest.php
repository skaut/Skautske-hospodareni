<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Model\Common\ShouldNotHappen;
use App\Model\Event\Enum\CampState;
use App\Model\Event\Enum\EventState;
use App\Model\Payment\Payment\State;
use App\Model\Utils\MoneyFactory;
use Codeception\Test\Unit;
use DateTimeImmutable;

/**
 * Latte filtry z AccountancyHelpers — jdou přímo do šablon, takže je hlídáme na výsledném HTML.
 */
final class AccountancyHelpersLabelsTest extends Unit
{
    private const NBSP = "\u{00A0}";

    /** @dataProvider provideEventStates */
    public function testEventStateLabel(string $state, string $expectedText, string $expectedClass): void
    {
        $label = AccountancyHelpers::eventStateLabel($state);

        self::assertStringContainsString($expectedText, $label);
        self::assertStringContainsString($expectedClass, $label);
    }

    /** @return array<string, array{string, string, string}> */
    public function provideEventStates(): array
    {
        return [
            'rozpracovaná' => [EventState::DRAFT->value, 'Rozpracováno', 'bg-warning'],
            'uzavřená' => [EventState::CLOSED->value, 'Uzavřeno', 'bg-success'],
            'cokoli jiného je zrušená' => ['neznámý', 'Zrušeno', 'bg-danger'],
        ];
    }

    /** @dataProvider provideCampStates */
    public function testCampStateLabel(string $state, string $expectedText): void
    {
        self::assertStringContainsString($expectedText, AccountancyHelpers::campStateLabel($state));
    }

    /** @return array<string, array{string, string}> */
    public function provideCampStates(): array
    {
        return [
            'rozpracovaný' => [CampState::DRAFT->value, 'Rozpracováno'],
            'schválený střediskem' => [CampState::APPROVED_PARENT->value, 'Schválený střediskem'],
            'schválený vedoucím' => [CampState::APPROVED_LEADER->value, 'Schválený vedoucím'],
            'skutečnost' => [CampState::REAL->value, 'Skutečnost odevzdána'],
            'jinak zrušený' => ['neznámý', 'Zrušený'],
        ];
    }

    /** @dataProvider provideEducationStates */
    public function testEducationStateLabel(string $state, string $expectedText): void
    {
        self::assertStringContainsString($expectedText, AccountancyHelpers::educationStateLabel($state));
    }

    /** @return array<string, array{string, string}> */
    public function provideEducationStates(): array
    {
        return [
            'draft' => ['draft', 'Rozpracováno'],
            'published' => ['published', 'Zveřejněno'],
            'confirmed' => ['confirmed', 'Potvrzeno vedoucím'],
            'waiting' => ['waiting', 'Čeká na schválení'],
            'approved' => ['approved', 'Schváleno'],
            'closed' => ['closed', 'Uzavřeno'],
            'rejected' => ['rejected', 'Potvrzení odmítnuto'],
            'disapproved' => ['disapproved', 'Schválení odmítnuto'],
            'jinak zrušené' => ['neznámý', 'Zrušeno'],
        ];
    }

    /** @dataProvider provideGrantStates */
    public function testGrantStateLabel(string $state, string $expectedText): void
    {
        self::assertStringContainsString($expectedText, AccountancyHelpers::grantStateLabel($state));
    }

    /** @return array<string, array{string, string}> */
    public function provideGrantStates(): array
    {
        return [
            'new' => ['new', 'Nová'],
            'unfinished' => ['unfinished', 'Rozpracováno'],
            'complement' => ['complement', 'Čeká na doplnění'],
            'waitsForAmendation' => ['waitsForAmendation', 'Čeká na doplnění'],
            'waitsForAdvanceSend' => ['waitsForAdvanceSend', 'Čeká na odeslání zálohy'],
            'waitsForConfirmation' => ['waitsForConfirmation', 'Čeká na potvrzení'],
            'waitsForAuthorization' => ['waitsForAuthorization', 'Čeká na potvrzení RK'],
            'waitsForDecisionApprove' => ['waitsForDecisionApprove', 'Čeká na potvrzení rozhodnutí'],
            'waitsForApproval' => ['waitsForApproval', 'Čeká na schválení'],
            'waitsForAudit' => ['waitsForAudit', 'Čeká na vyúčtování OJ'],
            'centralCheck' => ['centralCheck', 'Kontrola ústředím'],
            'submitted' => ['submitted', 'Odevzdáno'],
            'confirmed' => ['confirmed', 'Potvrzeno'],
            'approved' => ['approved', 'Schváleno'],
            'closed' => ['closed', 'Uzavřeno'],
            'running' => ['running', 'V realizaci OJ'],
            'denied' => ['denied', 'Zamítnuto'],
            'jinak zrušená' => ['neznámý', 'Zrušeno'],
        ];
    }

    public function testCommandStateShowsClosingDate(): void
    {
        self::assertStringContainsString('Rozpracovaný', AccountancyHelpers::commandState(null));

        $closed = AccountancyHelpers::commandState(new DateTimeImmutable('2026-07-15 14:30:05'));
        self::assertStringContainsString('Uzavřený', $closed);
        self::assertStringContainsString('Uzavřeno dne: 15.7.2026 14:30:05', $closed);
    }

    public function testPaymentStateLabelRendersBadgeForState(): void
    {
        $label = AccountancyHelpers::paymentStateLabel(State::get(State::COMPLETED));

        self::assertSame('span', $label->getName());
        self::assertStringContainsString('Dokončena', (string) $label);
        self::assertStringContainsString('bg-success', (string) $label);

        self::assertStringContainsString('bg-danger', (string) AccountancyHelpers::paymentStateLabel(State::get(State::CANCELED)));
        self::assertStringContainsString('bg-info', (string) AccountancyHelpers::paymentStateLabel(State::get(State::PREPARING)));
    }

    public function testPaymentStatePluralAndUnknownState(): void
    {
        self::assertSame('Nezaplacena', AccountancyHelpers::paymentState(State::PREPARING, false));
        self::assertSame('Nezaplacené', AccountancyHelpers::paymentState(State::PREPARING, true));
        self::assertSame('Zrušené', AccountancyHelpers::paymentState(State::CANCELED, true));
        self::assertSame('neznámý', AccountancyHelpers::paymentState('neznámý', false), 'neznámý stav se vypíše, jak přišel');
    }

    public function testGroupStateLabels(): void
    {
        self::assertStringContainsString('Otevřená', AccountancyHelpers::groupState('open'));
        self::assertStringContainsString('Uzavřená', AccountancyHelpers::groupState('closed'));

        $this->expectException(ShouldNotHappen::class);
        AccountancyHelpers::groupState('neznámý');
    }

    /** @dataProvider providePrices */
    public function testPriceFormatting(float|string|null $price, bool $full, string $expected): void
    {
        self::assertSame($expected, AccountancyHelpers::price($price, $full));
    }

    /** @return array<string, array{float|string|null, bool, string}> */
    public function providePrices(): array
    {
        // Oddělovač tisíců i prázdná hodnota jsou nedělitelné mezery (U+00A0).
        return [
            'null je nedělitelná mezera' => [null, true, self::NBSP],
            'prázdný string je nedělitelná mezera' => ['', true, self::NBSP],
            'desetiny' => [1234.5, true, '1'.self::NBSP.'234,50'],
            'zaokrouhlení bez desetin' => [1234.5, false, '1'.self::NBSP.'235'],
        ];
    }

    public function testPriceAcceptsMoney(): void
    {
        self::assertSame('1'.self::NBSP.'234,50', AccountancyHelpers::price(MoneyFactory::fromFloat(1234.5)));
    }

    public function testNumberFormattingKeepsDecimalsOnlyWhenPresent(): void
    {
        // Pozor: num() odděluje tisíce obyčejnou mezerou, price() nedělitelnou.
        self::assertSame('1 234', AccountancyHelpers::num(1234));
        self::assertSame('1 234,50', AccountancyHelpers::num(1234.5));
        self::assertSame('12', AccountancyHelpers::num('12'));
    }

    public function testPostCodeIsGroupedOnlyWhenItHasFiveDigits(): void
    {
        self::assertSame('110 00', AccountancyHelpers::postCode('11000'));
        self::assertSame('110 00', AccountancyHelpers::postCode('110 00'));
        self::assertSame('1234', AccountancyHelpers::postCode('1234'), 'nesmyslné PSČ zůstane beze změny');
    }

    /** @dataProvider providePricesInWords */
    public function testPriceToString(float $price, string $expected): void
    {
        self::assertSame($expected, AccountancyHelpers::priceToString($price));
    }

    /** @return array<string, array{float, string}> */
    public function providePricesInWords(): array
    {
        return [
            'jednotky' => [7.0, 'Sedm'],
            'teens' => [15.0, 'Patnáct'],
            'desítky s jednotkami' => [42.0, 'Čtyřicetdva'],
            'stovky' => [300.0, 'Třista'],
            'tisíce do čtyř' => [3000.0, 'Třitisíce'],
            'tisíce pod dvacet' => [15000.0, 'Patnácttisíc'],
            'tisíce pod sto' => [42000.0, 'Čtyřicetdvatisíc'],
            'stovky tisíc' => [123000.0, 'Jednostodvacettřitisíc'],
            'kombinace' => [1234.0, 'Jedentisícdvěstětřicetčtyři'],
            'příliš vysoké číslo' => [1234567.0, 'PŘÍLIŠ VYSOKÉ ČÍSLO'],
        ];
    }
}
