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

    public const UNIT_LEADER_ROLE = 'Středisko: vedoucí/admin - 621.66';
    public const UNIT_ID = 27266;

    /**
     * @throws Exception
     */
    public function login(string $role): void
    {
        $I = $this;

        if ($I->loadSessionSnapshot('login')) {
            $I->amOnPage('/');

            return;
        }

        $I->amOnPage('/');
        $I->click('[data-test="login-link"]');
        $I->waitForText('přihlášení');
        $I->fillField('(//input)[9]', self::LOGIN);
        $I->fillField('(//input)[10]', self::PASSWORD);
        $I->click('//button');
        $I->waitForText('Nástěnka');

        $roleButtonSelector = "//button[contains(@class, 'ui--current-role')]";

        if ($I->grabTextFrom($roleButtonSelector) !== $role) {
            $I->click($roleButtonSelector);
            $I->click("//a[text()='$role']");
            $I->waitForText($role);
        }

        $I->saveSessionSnapshot('login');
    }

    /**
     * Chrome can't work with popups ¯\_(ツ)_/¯.
     */
    public function disablePopups(): void
    {
        $this->executeJS('window.confirm = function(msg){return true;};');
    }

    public function clickStable(string $locator, int $timeout = 10): void
    {
        $this->waitForElementVisible($locator, $timeout);
        $this->waitForUiOverlaysToDisappear($timeout);
        $this->scrollElementToCenter($locator);
        $this->executeJS($this->buildLocatorScript($locator, 'el.click(); return true;'));
    }

    public function fillFieldStable(string $locator, string $value, int $timeout = 10): void
    {
        $this->waitForElementVisible($locator, $timeout);
        $this->waitForUiOverlaysToDisappear($timeout);
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

    public function waitForUiOverlaysToDisappear(int $timeout = 10): void
    {
        $this->waitForJS(
            'return document.querySelector(".modal-backdrop.show, .offcanvas-backdrop.show") === null;',
            $timeout,
        );
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
