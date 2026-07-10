<?php

declare(strict_types=1);

namespace acceptance;

use AcceptanceTester;
use PHPUnit\Framework\Assert;

final class PublicAccessCest extends BaseAcceptanceCest
{
    public function homepageUsesTestServerSettings(AcceptanceTester $I): void
    {
        $I->amOnPage('/');
        $I->waitForElementVisible('[data-test="homepage"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->waitForElementVisible('[data-test="test-server-badge"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        Assert::assertSame(
            'Testovací server',
            $I->grabAttributeFrom('[data-test="test-server-badge"]', 'title'),
        );
        $I->seeElement('.site-header.navbar--test');
    }

    /** @dataProvider publicPages */
    public function publicPagesRemainAccessible(AcceptanceTester $I, \Codeception\Example $example): void
    {
        $I->amOnPage($example['url']);
        $I->waitForElementVisible($example['selector'], AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
    }

    /** @dataProvider protectedPages */
    public function protectedPagesRedirectAnonymousUsersToHomepage(AcceptanceTester $I, \Codeception\Example $example): void
    {
        $I->amOnPage($example['url']);
        $I->waitForElementVisible('[data-test="homepage"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
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
