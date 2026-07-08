<?php

declare(strict_types=1);

namespace acceptance;

use AcceptanceTester;
use PHPUnit\Framework\Assert;

final class PublicAccessCest extends BaseAcceptanceCest
{
    /** @dataProvider publicPages */
    public function publicPagesRemainAccessible(AcceptanceTester $I, \Codeception\Example $example): void
    {
        $I->amOnPage($example['url']);
        $I->waitForElementVisible($example['selector'], 10);
    }

    /** @dataProvider protectedPages */
    public function protectedPagesRedirectAnonymousUsersToHomepage(AcceptanceTester $I, \Codeception\Example $example): void
    {
        $I->amOnPage($example['url']);
        $I->waitForElementVisible('[data-test="homepage"]', 10);
        Assert::assertSame('/', parse_url($I->grabFromCurrentUrl(), PHP_URL_PATH));
    }

    /**
     * @return array<string, array{url: string, selector: string}>
     */
    protected function publicPages(): array
    {
        return [
            'homepage' => ['url' => '/', 'selector' => '[data-test="homepage"]'],
            'about' => ['url' => '/o-projektu', 'selector' => 'h1'],
            'reinforcement' => ['url' => '/posily', 'selector' => 'h1'],
        ];
    }

    /**
     * @return array<string, array{url: string}>
     */
    protected function protectedPages(): array
    {
        return [
            'events' => ['url' => '/akce'],
            'bug-report' => ['url' => '/nahlasit-problem'],
            'download' => ['url' => '/cestaky/vozidla/download-scan/77?path=scan.pdf'],
        ];
    }
}
