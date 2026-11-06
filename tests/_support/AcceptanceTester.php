<?php

declare(strict_types=1);

use Codeception\Actor;
use Facebook\WebDriver\Exception\WebDriverException;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverExpectedCondition;
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
    public const PHONE_WIDTH = 375;
    public const PHONE_HEIGHT = 812;
    public const UNIT_LEADER_ROLE = 'Středisko: vedoucí/admin - 621.66';
    public const UNIT_ID = 27266;

    public function waitForElement(mixed $element, int $timeout = 10): void
    {
        try {
            $this->generatedWaitForElement($element, $timeout);
        } catch (WebDriverException $exception) {
            $this->throwOnSkautisWsdlErrorPage($exception, 'waiting for element', (string) json_encode($element));
        }
    }

    public function waitForElementVisible(mixed $element, int $timeout = 10): void
    {
        if (is_string($element)) {
            $this->waitForStableLocatorVisible($element, $timeout);

            return;
        }

        try {
            $this->generatedWaitForElementVisible($element, $timeout);
        } catch (WebDriverException $exception) {
            $this->throwOnSkautisWsdlErrorPage($exception, 'waiting for visible element', (string) json_encode($element));
        }
    }

    /**
     * Bootstrap moves `title` into `data-bs-original-title` as soon as it initializes a tooltip,
     * so a plain [title=...] assertion only holds before the frontend boots.
     */
    public function seeTooltip(string $text): void
    {
        $quoted = str_replace('"', '\\"', $text);

        $this->seeElement(
            '[data-bs-toggle="tooltip"][title="'.$quoted.'"],'
            .'[data-bs-toggle="tooltip"][data-bs-original-title="'.$quoted.'"]',
        );
    }

    public function waitForText(string $text, int $timeout = 10, mixed $selector = null): void
    {
        try {
            if ($selector === null) {
                $this->generatedWaitForText($text, $timeout);
            } else {
                $this->generatedWaitForText($text, $timeout, $selector);
            }
        } catch (WebDriverException $exception) {
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
        } catch (WebDriverException $exception) {
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
            } catch (WebDriverException $e) {
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

    public function waitForElementOrSkautisConnectionError(string $expectedSelector, int $timeout = self::ELEMENT_LOAD_TIMEOUT): string
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

    public function failBecauseSkautisConnectionFailedAfterRetries(
        string $actionDescription,
        string $expectedDescription,
        int $attempts,
    ): void {
        Assert::fail(
            'SkautIS WSDL communication failed after '.$attempts.' attempts while '.$actionDescription
            .'; expected '.$expectedDescription.' at '.$this->grabFromCurrentUrl()
            .'. SkautIS is an external system, so an outage of it is not an application defect'
            .' and cannot be worked around here. Re-run the test.',
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

    /**
     * Sdílené ovládání souboru (FileActionButton) u PDF: šipka u hlavní akce nabízí otevření v nové kartě.
     * U odkazů ověří i odpověď serveru – hlavní akce PDF stáhne, otevření ho zobrazí v prohlížeči.
     */
    public function seePdfFileAction(string $dataTest): void
    {
        $download = sprintf('[data-test="%s"]', $dataTest);
        $open = sprintf('[data-test="%s-open"]', $dataTest);

        $this->waitForElementVisible($download, self::ELEMENT_LOAD_TIMEOUT);
        $this->seeElement(sprintf('[data-test="%s-toggle"][data-bs-toggle="dropdown"]', $dataTest));

        $downloadUrl = $this->grabAttributeFrom($download, 'href');
        if ($downloadUrl === null) {
            // Formulářová tlačítka (hromadný tisk) nebo nedostupný soubor – kontroluje se jen ovládání.
            $this->seeElementInDOM($open.'[target="_blank"][rel="noopener noreferrer"], '.$open.'[formtarget="_blank"]');

            return;
        }

        $this->seeElementInDOM($open.'[target="_blank"][rel="noopener noreferrer"]');
        Assert::assertStringStartsWith('attachment;', $this->grabContentDisposition($downloadUrl), 'Hlavní akce má PDF stáhnout.');
        Assert::assertStringStartsWith('inline;', $this->grabContentDisposition((string) $this->grabAttributeFrom($open, 'href')), 'Otevření má PDF zobrazit.');
    }

    /** Excel, ZIP a ostatní formáty nabízejí jen stažení, bez volby otevření. */
    public function seeDownloadOnlyFileAction(string $dataTest): void
    {
        $this->waitForElementVisible(sprintf('[data-test="%s"]', $dataTest), self::ELEMENT_LOAD_TIMEOUT);
        $this->dontSeeElementInDOM(sprintf('[data-test="%s-toggle"]', $dataTest));
        $this->dontSeeElementInDOM(sprintf('[data-test="%s-open"]', $dataTest));
    }

    /**
     * Otevře PDF přes šipku sdíleného ovládání, počká na novou kartu s očekávanou adresou, zavře ji
     * a vrátí její adresu. Původní stránka zůstane, kde byla.
     */
    public function openPdfFileActionInNewTab(string $dataTest, string $expectedUrlPart): string
    {
        $urlBefore = $this->grabFromCurrentUrl();
        /** @var list<string> $handlesBefore */
        $handlesBefore = $this->executeInSelenium(static fn (RemoteWebDriver $webDriver): array => $webDriver->getWindowHandles());

        $this->clickStable(sprintf('[data-test="%s-toggle"]', $dataTest));
        $this->clickStable(sprintf('[data-test="%s-open"]', $dataTest));

        $openedUrl = (string) $this->executeInSelenium(static function (RemoteWebDriver $webDriver) use ($handlesBefore, $expectedUrlPart): string {
            $originalHandle = $webDriver->getWindowHandle();
            $webDriver->wait(self::ELEMENT_LOAD_TIMEOUT)->until(
                static fn (RemoteWebDriver $driver): bool => count($driver->getWindowHandles()) > count($handlesBefore),
            );
            $newHandles = array_values(array_diff($webDriver->getWindowHandles(), $handlesBefore));
            $webDriver->switchTo()->window($newHandles[0]);
            $webDriver->wait(self::ELEMENT_LOAD_TIMEOUT)->until(WebDriverExpectedCondition::urlContains($expectedUrlPart));
            $openedUrl = $webDriver->getCurrentURL();
            $webDriver->close();
            $webDriver->switchTo()->window($originalHandle);

            return $openedUrl;
        });

        $this->seeInCurrentUrl($urlBefore);

        return $openedUrl;
    }

    /**
     * Klikne na prvek, který odesílá ajaxový požadavek končící přesměrováním (naja `forceRedirect`),
     * a vrátí cílovou adresu přesměrování z odpovědi.
     */
    public function clickAndGrabAjaxRedirect(string $locator): string
    {
        $this->executeJS(
            'window.__ajaxRedirects = [];'
            .' if (window.__ajaxRedirectSpy !== true) {'
            .'   window.__ajaxRedirectSpy = true;'
            .'   const originalFetch = window.fetch;'
            .'   window.fetch = (...args) => originalFetch(...args).then(response => {'
            .'     response.clone().json().then(payload => { if (payload.redirect) window.__ajaxRedirects.push(payload.redirect); }).catch(() => {});'
            .'     return response;'
            .'   });'
            .' }',
        );
        $this->clickStable($locator);
        $this->waitForJS('return window.__ajaxRedirects.length > 0;', self::ELEMENT_LOAD_TIMEOUT);

        return (string) $this->executeJS('return window.__ajaxRedirects[0];');
    }

    /**
     * Na šířce telefonu stránka nesmí vodorovně přetékat a otevřená menu musí ležet celá ve viewportu.
     * Lokátory rozbalovacích tlačítek se před měřením postupně otevřou (např. menu a v něm vnořená šipka)
     * a po měření zase zavřou; velikost okna se vrátí na původní.
     */
    public function seeNoHorizontalOverflowOnPhone(string $context, string ...$dropdownToggles): void
    {
        $size = $this->executeInSelenium(static fn (RemoteWebDriver $webDriver) => $webDriver->manage()->window()->getSize());
        $this->resizeWindow(self::PHONE_WIDTH, self::PHONE_HEIGHT);

        foreach ($dropdownToggles as $toggle) {
            $this->clickStable($toggle);
            $this->waitForElement($toggle.'[aria-expanded="true"]', self::ELEMENT_LOAD_TIMEOUT);
        }

        $issues = $this->grabHorizontalOverflowIssues();

        foreach (array_reverse($dropdownToggles) as $toggle) {
            $this->clickStable($toggle);
        }
        $this->resizeWindow($size->getWidth(), $size->getHeight());

        Assert::assertSame([], $issues, sprintf('Rozložení na %d px – %s: %s', self::PHONE_WIDTH, $context, implode('; ', $issues)));
    }

    /**
     * Vodorovné přetečení stránky (obsah `.table-responsive` se posouvá uvnitř a nepočítá se)
     * a otevřená menu, která leží mimo viewport.
     *
     * @return list<string>
     */
    public function grabHorizontalOverflowIssues(): array
    {
        /** @var list<string> $issues */
        $issues = $this->executeJS(<<<'JS'
const width = document.documentElement.clientWidth;
const describe = el => el.tagName.toLowerCase()
    + (el.dataset.test ? '[data-test=' + el.dataset.test + ']' : '')
    + (el.className && typeof el.className === 'string' ? '.' + el.className.trim().split(/\s+/).slice(0, 3).join('.') : '');
const issues = [];
const overflow = document.documentElement.scrollWidth - width;

if (overflow > 0) {
    issues.push('stránka přetéká o ' + overflow + ' px');
    const offenders = [];
    document.querySelectorAll('body *').forEach(el => {
        if (el.parentElement && el.parentElement.closest('.table-responsive')) {
            return; // obsah tabulky se posouvá uvnitř obalu
        }
        const style = getComputedStyle(el);
        const rect = el.getBoundingClientRect();
        const right = rect.right + Math.max(0, parseFloat(style.marginRight) || 0);
        if (style.position !== 'fixed' && rect.width > 0 && right > width + 0.5) {
            offenders.push({ el, right });
        }
    });
    // Nahlásí se nejhlubší přetékající prvky, ne celý řetěz jejich předků.
    offenders
        .filter(({ el }) => ! offenders.some(other => other.el !== el && el.contains(other.el)))
        .forEach(({ el, right }) => issues.push(describe(el) + ' končí na ' + Math.round(right) + ' px'));
}

document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
    const rect = menu.getBoundingClientRect();
    if (rect.left < -0.5 || rect.right > width + 0.5) {
        issues.push('menu ' + describe(menu) + ' leží mimo viewport (' + Math.round(rect.left) + '–' + Math.round(rect.right) + ' px z ' + width + ')');
    }
});

return issues.slice(0, 10);
JS);

        return $issues;
    }

    /**
     * Prvek je vidět ve viewportu a nic jiného na stránce ho nepřekrývá – typicky položka
     * rozbalovacího menu, která by jinak mohla skončit pod následujícím obsahem.
     */
    /** Waits until the element matching the CSS selector has keyboard focus. */
    public function seeFocusOn(string $selector): void
    {
        $this->waitForJS(
            'return document.activeElement !== null && document.activeElement === document.querySelector('.json_encode($selector).');',
            self::ELEMENT_LOAD_TIMEOUT,
        );
    }

    public function seeElementNotCovered(string $selector): void
    {
        $this->waitForElementVisible($selector, self::ELEMENT_LOAD_TIMEOUT);

        $coveredBy = (string) $this->executeJS(<<<'JS'
const element = document.querySelector(arguments[0]);
const rect = element.getBoundingClientRect();
const x = rect.left + rect.width / 2;
const y = rect.top + rect.height / 2;
if (x < 0 || y < 0 || x > window.innerWidth || y > window.innerHeight) {
    return 'mimo viewport';
}
const topElement = document.elementFromPoint(x, y);
if (topElement === null || element === topElement || element.contains(topElement)) {
    return '';
}
return topElement.tagName.toLowerCase() + (topElement.dataset.test ? '[data-test=' + topElement.dataset.test + ']' : '')
    + (typeof topElement.className === 'string' && topElement.className ? '.' + topElement.className.trim().split(/\s+/).join('.') : '');
JS, [$selector]);

        Assert::assertSame('', $coveredBy, sprintf('Prvek %s překrývá %s.', $selector, $coveredBy));
    }

    /** Content-Disposition odpovědi na adresu v rámci přihlášené relace. */
    public function grabContentDisposition(string $url): string
    {
        return (string) $this->executeAsyncJS(
            'const done = arguments[arguments.length - 1];'
            .' fetch(arguments[0], { credentials: "same-origin" })'
            .'.then(response => done(response.headers.get("content-disposition") ?? "HTTP " + response.status))'
            .'.catch(error => done("fetch failed: " + error));',
            [$url],
        );
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
        // Reported through the locator rather than through waitForJS, whose failure message
        // would be the whole generated script instead of the element that was waited for.
        try {
            $this->generatedWaitForJS('return '.$this->buildLocatorScript($locator, 'return true;').';', $timeout);
        } catch (WebDriverException $exception) {
            $this->throwOnSkautisWsdlErrorPage($exception, 'waiting for visible element', $locator);
        }
    }

    private function isRetryableSkautisLoginWebDriverFailure(WebDriverException $e): bool
    {
        $message = $e->getMessage();

        return str_contains($message, 'Timed out receiving message from renderer')
            || str_contains($message, "doesn't evaluate to true");
    }

    private function throwOnSkautisWsdlErrorPage(
        WebDriverException $exception,
        string $actionDescription,
        string $expectedDescription,
    ): void {
        if (! $this->isSkautisWsdlErrorPage()) {
            throw $exception;
        }

        throw new SkautisWsdlPageException('SkautIS WSDL communication failed while '.$actionDescription.'; expected '.$expectedDescription.' at '.$this->grabFromCurrentUrl().'. The rendered page is a SkautIS outage, not an application defect. Re-run the test; if this keeps happening here, route the step through the shared SkautIS retry helper in BaseAcceptanceCest.');
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

    /**
     * Resolves the first visible match instead of the first match in the DOM. Grids render
     * row actions twice - in the actions cell and in the following actions row - and a
     * container query decides which copy is shown, so the first match is often the hidden one.
     *
     * Visibility has to stay as strict as WebDriver's own check, otherwise a wait succeeds
     * before the element can be interacted with. An element inside a collapsing Bootstrap
     * panel keeps its own box while the panel is still zero height, so clipping by an
     * ancestor has to be taken into account - but only where the ancestor really hides the
     * overflow. Content that merely sits outside a scrollable ancestor, such as a wide grid
     * inside `.table-responsive`, stays reachable and therefore counts as visible.
     */
    private function buildLocatorScript(string $locator, string $action): string
    {
        $quotedLocator = json_encode($locator);

        return '(function(){'
            .'var locator = '.$quotedLocator.';'
            .'var nodes = [];'
            .'if (locator.startsWith("//") || locator.startsWith("(") || locator.startsWith(".//")) {'
                .'var found = document.evaluate(locator, document, null, XPathResult.ORDERED_NODE_SNAPSHOT_TYPE, null);'
                .'for (var i = 0; i < found.snapshotLength; i++) { nodes.push(found.snapshotItem(i)); }'
            .'} else {'
                .'nodes = Array.prototype.slice.call(document.querySelectorAll(locator));'
            .'}'
            .'var el = nodes.filter(function (node) {'
                .'var style = window.getComputedStyle(node);'
                .'if (style.display === "none" || style.visibility === "hidden" || style.opacity === "0") {'
                    .'return false;'
                .'}'
                .'var rect = node.getBoundingClientRect();'
                .'if (rect.width <= 0 || rect.height <= 0) { return false; }'
                .'for (var parent = node.parentElement; parent !== null; parent = parent.parentElement) {'
                    .'var parentStyle = window.getComputedStyle(parent);'
                    .'var clipsX = parentStyle.overflowX === "hidden" || parentStyle.overflowX === "clip";'
                    .'var clipsY = parentStyle.overflowY === "hidden" || parentStyle.overflowY === "clip";'
                    .'if (! clipsX && ! clipsY) { continue; }'
                    .'var clip = parent.getBoundingClientRect();'
                    .'if (clipsX && (rect.right <= clip.left || rect.left >= clip.right)) { return false; }'
                    .'if (clipsY && (rect.bottom <= clip.top || rect.top >= clip.bottom)) { return false; }'
                .'}'
                .'return true;'
            .'})[0];'
            .'if (!el) { return false; }'
            .$action
            .'})()';
    }
}
