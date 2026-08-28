<?php

declare(strict_types=1);

namespace acceptance;

use AcceptanceTester;
use PHPUnit\Framework\Assert;

use function implode;
use function sprintf;

/**
 * Guards the two layout rules from the UI guideline that are easy to break by
 * accident and invisible in a code review: no horizontal page overflow on a phone,
 * and a 44 x 44 px target for every control that is not part of running text.
 *
 * Run through `make ci-acceptance` for a real 375 px viewport - a windowed Chrome
 * cannot go below roughly 500 px, so a local run checks the same rules on a wider
 * screen.
 *
 * The grid pages carry whatever rows the rest of the suite happened to create, so a
 * full-suite run exercises table cells that an isolated run cannot reach.
 */
// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
class ResponsiveCest extends BaseAcceptanceCest
{
    private const PAGES = [
        '/nastenka' => '[data-test="dashboard"]',
        '/cestaky/vozidla' => '[data-test="travel-vehicles-page"]',
        '/platby/skupiny' => '[data-test="payments-groups-page"]',
        '/cestaky/smlouvy' => '[data-test="travel-contracts-page"]',
        '/nastaveni' => '[data-test="settings-page"]',
    ];

    protected AcceptanceTester $I;

    public function _before(AcceptanceTester $I): void
    {
        parent::_before($I);

        $this->I = $I;
        $I->login(AcceptanceTester::UNIT_LEADER_ROLE);
    }

    /** @group responsive */
    public function narrowScreenHasNoHorizontalOverflowAndUsableTargets(): void
    {
        $I = $this->I;

        $I->wantTo('keep phone layouts free of horizontal overflow and tiny targets');

        $I->resizeWindow(375, 900);

        foreach (self::PAGES as $page => $marker) {
            $I->amOnPage($page);
            $I->waitForElementVisible($marker, AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

            $measured = $I->executeJS(<<<'JS'
const selector = 'a[href], button, input[type=checkbox], input[type=radio], [role=button]';
const small = [];

document.querySelectorAll(selector).forEach(el => {
    const rect = el.getBoundingClientRect();

    if (rect.width === 0 || rect.height === 0) {
        return;
    }

    // A stretched link is only a marker: the real target is the whole card.
    if (el.classList.contains('stretched-link')) {
        return;
    }

    // Links inside running text are exempt by the guideline.
    if (el.closest('p, .page-lead, .site-footer__text, [data-help-content]')) {
        return;
    }

    if (rect.width < 44 || rect.height < 44) {
        small.push(Math.round(rect.width) + 'x' + Math.round(rect.height) + ' ' +
            el.tagName.toLowerCase() + '.' + (el.className || '').toString().split(' ').slice(0, 2).join('.'));
    }
});

return {
    viewport: window.innerWidth,
    overflow: document.documentElement.scrollWidth - document.documentElement.clientWidth,
    small: small,
};
JS);

            Assert::assertSame(
                0,
                $measured['overflow'],
                sprintf('Vodorovné přetečení na %s (viewport %d px)', $page, $measured['viewport']),
            );
            Assert::assertSame(
                [],
                $measured['small'],
                sprintf('Malé dotykové cíle na %s: %s', $page, implode(', ', $measured['small'])),
            );
        }
    }
}
