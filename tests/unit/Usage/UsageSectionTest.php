<?php

declare(strict_types=1);

namespace App\Model\Usage;

use Codeception\Test\Unit as TestCase;

final class UsageSectionTest extends TestCase
{
    /** @return array<string, array{string, UsageSection|null}> */
    public static function presenters(): array
    {
        return [
            'payment group' => ['Payments:Payment', UsageSection::PAYMENTS],
            'invoices live in payments' => ['Payments:InvoiceList', UsageSection::INVOICES],
            'invoice settings' => ['Settings:Invoices', UsageSection::INVOICES],
            'unit cashbook' => ['Unit:Cashbook', UsageSection::UNIT_CASHBOOK],
            'event' => ['Events:Event', UsageSection::EVENTS],
            'camp' => ['Camps:Detail', UsageSection::CAMPS],
            'education' => ['Education:Education', UsageSection::EDUCATION],
            'travel command' => ['Travel:Command', UsageSection::TRAVEL],
            'settings' => ['Settings:BankAccounts', UsageSection::SETTINGS],
            'dashboard' => ['Dashboard', UsageSection::OTHER],
            'administration' => ['Admin:Usage', null],
            'public page' => ['Default', null],
            'sign in' => ['Auth', null],
            'keep alive' => ['SessionKeepAlive', null],
        ];
    }

    /** @dataProvider presenters */
    public function testPresenterBelongsToASection(string $presenterName, ?UsageSection $expected): void
    {
        self::assertSame($expected, UsageSection::fromPresenterName($presenterName));
    }

    public function testScreenWithoutANameShowsThePresenter(): void
    {
        self::assertSame('Detail skupiny', UsageScreen::label('Payments:Payment'));
        self::assertSame('Payments:Unknown', UsageScreen::label('Payments:Unknown'));
    }
}
