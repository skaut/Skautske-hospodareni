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

    public function everyPageOffersInstallationOfTheApplication(AcceptanceTester $I): void
    {
        $I->amOnPage('/');
        $I->waitForElementVisible('[data-test="homepage"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        $I->seeElementInDOM('link[rel="manifest"][href="/manifest.webmanifest"]');
        $I->seeElementInDOM('link[rel="apple-touch-icon"][href="/images/pwa/icon-apple-touch.png"]');

        // The offer belongs to phones that can install the application, so on
        // a desktop browser it must stay in the page but out of sight. The manual
        // instruction is for iOS only and stays hidden even inside the offer.
        $I->seeElementInDOM('[data-test="app-install-banner"][hidden]');
        $I->dontSeeElement('[data-test="app-install-banner"]');
        $I->seeElementInDOM('[data-test="app-install-hint"][hidden]');
    }

    /**
     * The layout used to load Google Analytics on every production page. The property
     * had been switched off for years, so the request bought nothing — and the tag
     * must not quietly come back with a copied snippet.
     */
    public function noPageLoadsExternalAnalytics(AcceptanceTester $I): void
    {
        $I->amOnPage('/');
        $I->waitForElementVisible('[data-test="homepage"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        $I->dontSeeElementInDOM('script[src*="google-analytics.com"]');
        $I->dontSeeElementInDOM('script[src*="googletagmanager.com"]');

        $html = $I->executeJS('return document.documentElement.innerHTML');
        Assert::assertStringNotContainsString('UA-50892244', $html);
        Assert::assertStringNotContainsString('google-analytics', $html);
        Assert::assertStringNotContainsString('gtag(', $html);

        // What replaced it: a description of the measurement instead of a foreign request.
        $I->seeElement('[data-test="footer-privacy-link"]');
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
            'privacy' => ['url' => '/zasady-soukromi', 'selector' => '[data-test="privacy-page"]'],
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
