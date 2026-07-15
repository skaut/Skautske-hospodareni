<?php

declare(strict_types=1);

use Codeception\Actor;

/**
 * Inherited Methods.
 * @method void                    wantToTest($text)
 * @method void                    wantTo($text)
 * @method void                    execute($callable)
 * @method void                    expectTo($prediction)
 * @method void                    expect($prediction)
 * @method void                    amGoingTo($argumentation)
 * @method void                    am($role)
 * @method void                    lookForwardTo($achieveValue)
 * @method void                    comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = NULL)
 *
 * @SuppressWarnings(PHPMD)
 */
class AcceptanceTester extends Actor
{
    use _generated\AcceptanceTesterActions;

    private const LOGIN = 'crash01';

    private const PASSWORD = 'chtelbysprachy1';

    private const LOGIN_TRIGGER_SELECTOR = '[data-test="login-link"], [data-test="homepage-login"]';
    public const ELEMENT_LOAD_TIMEOUT = 30;
    public const UNIT_LEADER_ROLE = 'Středisko: vedoucí/admin - 621.66';
    public const UNIT_ID = 27266;

    /**
     * @throws Exception
     */
    public function login(string $role): void
    {
        $I = $this;

        if ($I->loadSessionSnapshot('login')) {
            $I->amOnPage('/nastenka');

            $I->waitForDocumentReady();
            $hasExpectedRole = $I->executeJS(
                'const roleButton = document.querySelector(".ui--current-role");'
                .'return roleButton !== null && roleButton.textContent.trim() === '.json_encode($role).';',
            );
            if ($hasExpectedRole) {
                return;
            }
        }

        $I->amOnPage('/');
        $I->waitForDocumentReady();
        $I->waitForLoginTrigger();
        $I->clickFirstLoginTrigger();
        $I->waitForText('přihlášení', self::ELEMENT_LOAD_TIMEOUT);
        $I->fillField('(//input)[9]', self::LOGIN);
        $I->fillField('(//input)[10]', self::PASSWORD);
        $I->click('//button');
        $I->waitForText('Nástěnka', self::ELEMENT_LOAD_TIMEOUT);

        $roleButtonSelector = "//button[contains(@class, 'ui--current-role')]";

        if ($I->grabTextFrom($roleButtonSelector) !== $role) {
            $I->click($roleButtonSelector);
            $I->click("//a[text()='$role']");
            $I->waitForText($role, self::ELEMENT_LOAD_TIMEOUT);
        }

        $I->saveSessionSnapshot('login');
    }

    public function waitForDocumentReady(int $timeout = self::ELEMENT_LOAD_TIMEOUT): void
    {
        $this->waitForJS(
            'return document.readyState === "interactive" || document.readyState === "complete";',
            $timeout,
        );
    }

    public function waitForLoginTrigger(int $timeout = self::ELEMENT_LOAD_TIMEOUT): void
    {
        $selector = json_encode(self::LOGIN_TRIGGER_SELECTOR);

        $this->waitForJS(
            'const el = document.querySelector('.$selector.');'
            .'return el !== null;',
            $timeout,
        );
    }

    public function waitForPageTextStable(string $text, int $timeout = self::ELEMENT_LOAD_TIMEOUT): void
    {
        $this->waitForJS(
            'return document.body !== null && document.body.textContent.includes('.json_encode($text).');',
            $timeout,
        );
    }

    public function clickFirstLoginTrigger(): void
    {
        $selector = json_encode(self::LOGIN_TRIGGER_SELECTOR);

        $this->executeJS(
            'const el = document.querySelector('.$selector.');'
            .'if (el === null) { throw new Error("Login trigger was not found."); }'
            .'el.click();',
        );
    }

    /**
     * Chrome can't work with popups ¯\_(ツ)_/¯.
     */
    public function disablePopups(): void
    {
        $this->executeJS('window.confirm = function(msg){return true;};');
    }

    public function clickStable(string $locator, int $timeout = self::ELEMENT_LOAD_TIMEOUT, bool $waitForOverlays = true): void
    {
        $this->waitForStableLocatorVisible($locator, $timeout);
        if ($waitForOverlays) {
            $this->waitForUiOverlaysToDisappear($timeout);
        }
        $this->scrollElementToCenter($locator);
        $this->executeJS($this->buildLocatorScript($locator, 'el.click(); return true;'));
    }

    public function fillFieldStable(string $locator, string $value, int $timeout = self::ELEMENT_LOAD_TIMEOUT, bool $waitForOverlays = true): void
    {
        $this->waitForStableLocatorVisible($locator, $timeout);
        if ($waitForOverlays) {
            $this->waitForUiOverlaysToDisappear($timeout);
        }
        $this->scrollElementToCenter($locator);
        $this->executeJS($this->buildLocatorScript(
            $locator,
            'el.focus(); el.value = ""; el.dispatchEvent(new Event("input", { bubbles: true }));'
            .' el.value = '.json_encode($value).';'
            .' el.dispatchEvent(new Event("input", { bubbles: true }));'
            .' el.dispatchEvent(new Event("change", { bubbles: true }));'
            .' return true;',
        ));
    }

    public function waitForUiOverlaysToDisappear(int $timeout = self::ELEMENT_LOAD_TIMEOUT): void
    {
        $this->waitForJS(
            'return document.querySelector(".modal-backdrop.show, .offcanvas-backdrop.show") === null;',
            $timeout,
        );
    }

    public function waitForStableLocatorVisible(string $locator, int $timeout = self::ELEMENT_LOAD_TIMEOUT): void
    {
        if (! $this->isXPathLocator($locator)) {
            $this->waitForElementVisible($locator, $timeout);

            return;
        }

        $this->waitForJS($this->buildLocatorScript(
            $locator,
            'var style = window.getComputedStyle(el);'
            .' return style.display !== "none" && style.visibility !== "hidden" && el.getClientRects().length > 0;',
        ), $timeout);
    }

    private function isXPathLocator(string $locator): bool
    {
        return str_starts_with($locator, '//') || str_starts_with($locator, '(') || str_starts_with($locator, './/');
    }

    private function scrollElementToCenter(string $locator): void
    {
        $this->executeJS($this->buildLocatorScript(
            $locator,
            'el.scrollIntoView({block: "center", inline: "center"}); return true;',
        ));
    }

    private function buildLocatorScript(string $locator, string $action): string
    {
        $quotedLocator = json_encode($locator);

        return '(function(){'
            .'var locator = '.$quotedLocator.';'
            .'var el = null;'
            .'if (locator.startsWith("//") || locator.startsWith("(") || locator.startsWith(".//")) {'
                .'el = document.evaluate(locator, document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue;'
            .'} else {'
                .'el = document.querySelector(locator);'
            .'}'
            .'if (!el) { return false; }'
            .$action
            .'})()';
    }
}
