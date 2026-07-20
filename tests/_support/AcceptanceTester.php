<?php

declare(strict_types=1);

use Codeception\Actor;
use Facebook\WebDriver\Exception\TimeoutException;
use PHPUnit\Framework\Assert;

final class SkautisWsdlPageException extends RuntimeException
{
}

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
    use _generated\AcceptanceTesterActions {
        waitForElement as private generatedWaitForElement;
        waitForElementVisible as private generatedWaitForElementVisible;
        waitForJS as private generatedWaitForJS;
        waitForText as private generatedWaitForText;
    }

    public const PAGE_STATE_EXPECTED = 'expected';
    public const PAGE_STATE_SKAUTIS_UNAVAILABLE = 'skautis-unavailable';
    public const PAGE_STATE_UNKNOWN = 'unknown';

    private const SKAUTIS_CONNECTION_ERROR_TEXTS = [
        'Could not connect to host',
        'Error Fetching http headers',
        'php_network_getaddresses',
    ];

    private const LOGIN = 'crash01';

    private const PASSWORD = 'chtelbysprachy1';
    private const LOGIN_ATTEMPTS = 3;

    private const LOGIN_TRIGGER_SELECTOR = '[data-test="login-link"], [data-test="homepage-login"]';
    private const CURRENT_ROLE_SELECTOR = '.ui--current-role';
    public const ELEMENT_LOAD_TIMEOUT = 30;
    public const UNIT_LEADER_ROLE = 'Středisko: vedoucí/admin - 621.66';
    public const UNIT_ID = 27266;

    public function waitForElement($element, int $timeout = 10): void
    {
        try {
            $this->generatedWaitForElement($element, $timeout);
        } catch (TimeoutException $exception) {
            $this->throwOnSkautisWsdlErrorPage($exception, 'waiting for element', (string) json_encode($element));
        }
    }

    public function waitForElementVisible($element, int $timeout = 10): void
    {
        try {
            $this->generatedWaitForElementVisible($element, $timeout);
        } catch (TimeoutException $exception) {
            $this->throwOnSkautisWsdlErrorPage($exception, 'waiting for visible element', (string) json_encode($element));
        }
    }

    public function waitForText(string $text, int $timeout = 10, $selector = null): void
    {
        try {
            if ($selector === null) {
                $this->generatedWaitForText($text, $timeout);
            } else {
                $this->generatedWaitForText($text, $timeout, $selector);
            }
        } catch (TimeoutException $exception) {
            $expected = $selector === null
                ? $text
                : $text.' in '.(string) json_encode($selector);
            $this->throwOnSkautisWsdlErrorPage($exception, 'waiting for text', $expected);
        }
    }

    public function waitForJS(string $script, int $timeout = 5): void
    {
        try {
            $this->generatedWaitForJS($script, $timeout);
        } catch (TimeoutException $exception) {
            $this->throwOnSkautisWsdlErrorPage($exception, 'waiting for JavaScript condition', $script);
        }
    }

    /**
     * @throws Exception
     */
    public function login(string $role): void
    {
        $I = $this;

        if ($I->loadSessionSnapshot('login')) {
            $I->amOnPage('/nastenka');

            $I->waitForDocumentReady();
            if ($I->hasExpectedRole($role)) {
                return;
            }
        }

        $I->openLoginFormOrExpectedRole($role);
        if ($I->hasExpectedRole($role)) {
            $I->saveSessionSnapshot('login');

            return;
        }

        $I->submitSkautisLoginWithRetry($role);

        $roleButtonSelector = "//button[contains(@class, 'ui--current-role')]";

        if ($I->grabTextFrom($roleButtonSelector) !== $role) {
            $I->click($roleButtonSelector);
            $I->click("//a[text()='$role']");
            $I->waitForText($role, self::ELEMENT_LOAD_TIMEOUT);
        }

        $I->saveSessionSnapshot('login');
    }

    public function openLoginFormOrExpectedRole(string $role): void
    {
        $this->amOnPage('/');
        $this->waitForDocumentReady();
        $this->waitForLoginTrigger();
        $this->clickFirstLoginTrigger();
        $this->waitForLoginFormOrExpectedRole($role);
    }

    public function submitSkautisLoginWithRetry(string $role): void
    {
        for ($attempt = 1; $attempt <= self::LOGIN_ATTEMPTS; ++$attempt) {
            $this->waitForText('přihlášení', self::ELEMENT_LOAD_TIMEOUT);
            $this->fillField('(//input)[9]', self::LOGIN);
            $this->fillField('(//input)[10]', self::PASSWORD);

            try {
                $this->submitSkautisLoginForm();
                $this->waitForLoginSuccessOrSkautisDnsError();
            } catch (Facebook\WebDriver\Exception\WebDriverException $e) {
                if (! $this->isRetryableSkautisLoginWebDriverFailure($e) || $attempt === self::LOGIN_ATTEMPTS) {
                    throw $e;
                }
            }

            if ($this->hasLoginSucceeded()) {
                return;
            }

            if (! $this->hasSkautisDnsError() && ! $this->isOnSkautisLoginForm()) {
                break;
            }

            if ($attempt < self::LOGIN_ATTEMPTS) {
                $this->openLoginFormOrExpectedRole($role);
            }
        }

        $this->waitForText('Nástěnka', self::ELEMENT_LOAD_TIMEOUT);
    }

    public function submitSkautisLoginForm(): void
    {
        $this->waitForJS(
            'const button = document.querySelector("#ctl00_Content_BtnLogin, button[type=submit]");'
            .'return button !== null && !button.disabled;',
            self::ELEMENT_LOAD_TIMEOUT,
        );
        $this->executeJS(
            'const button = document.querySelector("#ctl00_Content_BtnLogin, button[type=submit]");'
            .'if (button === null) { throw new Error("SkautIS login submit was not found."); }'
            .'button.click();'
            .'return true;',
        );
    }

    public function hasExpectedRole(string $role): bool
    {
        return (bool) $this->executeJS(
            'const roleButton = document.querySelector('.json_encode(self::CURRENT_ROLE_SELECTOR).');'
            .'return roleButton !== null && roleButton.textContent.trim() === '.json_encode($role).';',
        );
    }

    public function waitForLoginFormOrExpectedRole(string $role, int $timeout = self::ELEMENT_LOAD_TIMEOUT): void
    {
        $this->waitForJS(
            'const roleButton = document.querySelector('.json_encode(self::CURRENT_ROLE_SELECTOR).');'
            .'const hasExpectedRole = roleButton !== null && roleButton.textContent.trim() === '.json_encode($role).';'
            .'const hasLoginForm = document.body !== null && document.body.textContent.includes("přihlášení");'
            .'return hasExpectedRole || hasLoginForm;',
            $timeout,
        );
    }

    public function waitForLoginSuccessOrSkautisDnsError(int $timeout = self::ELEMENT_LOAD_TIMEOUT): void
    {
        $this->waitForJS(
            'const text = document.body?.textContent ?? "";'
            .'return text.includes("Nástěnka") || '
            .'(text.includes("test-is.skaut.cz") && text.includes("php_network_getaddresses"));',
            $timeout,
        );
    }

    public function hasLoginSucceeded(): bool
    {
        return (bool) $this->executeJS(
            'return document.body !== null && document.body.textContent.includes("Nástěnka");',
        );
    }

    public function hasSkautisDnsError(): bool
    {
        return (bool) $this->executeJS(
            'const text = document.body?.textContent ?? "";'
            .'return text.includes("test-is.skaut.cz") && text.includes("php_network_getaddresses");',
        );
    }

    private function waitForElementOrSkautisConnectionError(string $expectedSelector, int $timeout = self::ELEMENT_LOAD_TIMEOUT): string
    {
        $selector = json_encode($expectedSelector);
        $skautisErrorCondition = $this->buildSkautisWsdlErrorJsCondition('text');

        $this->generatedWaitForJS(
            'const expected = document.querySelector('.$selector.') !== null;'
            .'const text = document.body?.textContent ?? "";'
            .'return expected || ('.$skautisErrorCondition.');',
            $timeout,
        );

        return (string) $this->executeJS(
            'const text = document.body?.textContent ?? "";'
            .'if (document.querySelector('.$selector.') !== null) { return '.json_encode(self::PAGE_STATE_EXPECTED).'; }'
            .'if ('.$skautisErrorCondition.') { return '.json_encode(self::PAGE_STATE_SKAUTIS_UNAVAILABLE).'; }'
            .'return '.json_encode(self::PAGE_STATE_UNKNOWN).';',
        );
    }

    public function waitForPageTextOrSkautisConnectionError(string $expectedText, int $timeout = self::ELEMENT_LOAD_TIMEOUT): string
    {
        $text = json_encode($expectedText);
        $skautisErrorCondition = $this->buildSkautisWsdlErrorJsCondition('bodyText');

        $this->generatedWaitForJS(
            'const bodyText = document.body?.textContent ?? "";'
            .'return bodyText.includes('.$text.') || '
            .'('.$skautisErrorCondition.');',
            $timeout,
        );

        return (string) $this->executeJS(
            'const bodyText = document.body?.textContent ?? "";'
            .'if (bodyText.includes('.$text.')) { return '.json_encode(self::PAGE_STATE_EXPECTED).'; }'
            .'if ('.$skautisErrorCondition.') { return '.json_encode(self::PAGE_STATE_SKAUTIS_UNAVAILABLE).'; }'
            .'return '.json_encode(self::PAGE_STATE_UNKNOWN).';',
        );
    }

    public function clickLinkAndWaitForElementWithSkautisRetry(
        string $linkSelector,
        string $expectedSelector,
        ?string $expectedUrlPart,
        int $attempts,
        int $retryDelaySeconds,
    ): void {
        $linkUrl = null;
        $pageState = 'unknown';

        for ($attempt = 1; $attempt <= $attempts; ++$attempt) {
            if ($linkUrl === null) {
                $this->waitForElementVisible($linkSelector, self::ELEMENT_LOAD_TIMEOUT);
                $linkUrl = (string) $this->grabAttributeFrom($linkSelector, 'href');
                $this->clickStable($linkSelector);
            } else {
                $this->amOnPage($linkUrl);
            }

            $pageState = $this->waitForElementOrSkautisConnectionError($expectedSelector);

            if ($pageState !== self::PAGE_STATE_SKAUTIS_UNAVAILABLE || $attempt === $attempts) {
                break;
            }

            sleep($retryDelaySeconds);
        }

        if ($pageState === self::PAGE_STATE_SKAUTIS_UNAVAILABLE) {
            $this->failBecauseSkautisConnectionFailedAfterRetries(
                'opening '.$linkUrl.' via '.$linkSelector,
                $expectedSelector,
                $attempts,
            );
        }

        if ($pageState !== self::PAGE_STATE_EXPECTED) {
            Assert::fail(
                'Expected '.$expectedSelector.' after opening '.$linkUrl.' via '.$linkSelector
                .', got '.$pageState.' state at '.$this->grabFromCurrentUrl().'.',
            );
        }

        if ($expectedUrlPart !== null) {
            $this->seeInCurrentUrl($expectedUrlPart);
        }
    }

    public function failBecauseSkautisConnectionFailedAfterRetries(
        string $actionDescription,
        string $expectedDescription,
        int $attempts,
    ): void {
        Assert::fail(
            'SkautIS WSDL communication failed after '.$attempts.' attempts while '.$actionDescription
            .'; expected '.$expectedDescription.' at '.$this->grabFromCurrentUrl()
            .'. Last rendered page contains a retryable WsdlException.',
        );
    }

    public function isOnSkautisLoginForm(): bool
    {
        return (bool) $this->executeJS(
            'return document.querySelector("#ctl00_Content_BtnLogin, button[type=submit]") !== null'
            .' && (document.body?.textContent ?? "").includes("přihlášení");',
        );
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

    private function isRetryableSkautisLoginWebDriverFailure(Facebook\WebDriver\Exception\WebDriverException $e): bool
    {
        $message = $e->getMessage();

        return str_contains($message, 'Timed out receiving message from renderer')
            || str_contains($message, "doesn't evaluate to true");
    }

    private function throwOnSkautisWsdlErrorPage(
        TimeoutException $exception,
        string $actionDescription,
        string $expectedDescription,
    ): void {
        if (! $this->isSkautisWsdlErrorPage()) {
            throw $exception;
        }

        throw new SkautisWsdlPageException('SkautIS WSDL communication failed while '.$actionDescription.'; expected '.$expectedDescription.' at '.$this->grabFromCurrentUrl().'. Last rendered page contains a retryable WsdlException.');
    }

    private function isSkautisWsdlErrorPage(): bool
    {
        try {
            return (bool) $this->executeJS(
                'const text = document.body?.textContent ?? "";'
                .'return '.$this->buildSkautisWsdlErrorJsCondition('text').';',
            );
        } catch (Throwable) {
            return false;
        }
    }

    private function buildSkautisWsdlErrorJsCondition(string $textVariable): string
    {
        $conditions = [];
        foreach (self::SKAUTIS_CONNECTION_ERROR_TEXTS as $errorText) {
            $conditions[] = $textVariable.'.includes('.json_encode($errorText).')';
        }

        return $textVariable.'.includes("WsdlException") && ('.implode(' || ', $conditions).')';
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
