<?php

declare(strict_types=1);

namespace acceptance;

use AcceptanceTester;
use PHPUnit\Framework\Assert;

final class BugReportCest extends BaseAcceptanceCest
{
    private const ACCEPTANCE_ADMIN_USER_ID = 2465;

    protected AcceptanceTester $I;

    public function _before(AcceptanceTester $I): void
    {
        parent::_before($I);

        $this->I = $I;
        $I->login(AcceptanceTester::UNIT_LEADER_ROLE);
    }

    /** @group bug-report */
    public function userCanSubmitTechnicalErrorReportAndAdminCanInspectIt(): void
    {
        $I = $this->I;
        $description = 'Acceptance technická chyba '.uniqid('', true);
        $reportedUrl = 'http://moje-hospodareni.cz/platby?test=bug-report';

        $I->amOnPage('/nastenka');
        $I->waitForElementVisible('[data-test="dashboard"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeElement('[data-test="footer-bug-report-link"]');
        $I->clickStable('[data-test="footer-bug-report-link"]');

        $I->waitForElementVisible('[data-test="bug-report-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->see('výhradně k hlášení technických chyb');
        $I->see('Není to helpdesk');
        $I->seeElement('[data-test="bug-report-help"]');
        $I->seeElement('[data-test="bug-report-form"] textarea[name="description"]');
        $I->seeElement('[data-test="bug-report-form"] input[name="url"]');
        $I->dontSeeElement('[data-test="bug-report-form"] input[name="url"][required]');

        $prefilledUrl = (string) $I->grabValueFrom('[data-test="bug-report-form"] input[name="url"]');
        Assert::assertStringContainsString('/nastenka', $prefilledUrl);

        $hasBrowserDiagnostics = $I->executeJS(<<<'JS'
            const field = document.querySelector('[data-bug-report-diagnostics]');
            if (!field || !field.value) {
                return false;
            }
            const diagnostics = JSON.parse(field.value);
            return diagnostics.viewport?.innerWidth > 0
                && diagnostics.screen?.width > 0
                && typeof diagnostics.locale?.timezone === 'string';
            JS);
        Assert::assertTrue($hasBrowserDiagnostics);

        $hasReporterEmailField = (bool) $I->executeJS(<<<'JS'
            return document.querySelector('[data-test="bug-report-form"] input[name="reporterEmail"]') !== null;
            JS);
        if ($hasReporterEmailField) {
            $I->fillField('[data-test="bug-report-form"] input[name="reporterEmail"]', 'acceptance.reporter@example.test');
        }

        $I->fillField('[data-test="bug-report-form"] textarea[name="description"]', $description);
        $I->fillField('[data-test="bug-report-form"] input[name="url"]', $reportedUrl);
        $I->clickStable('[data-test="bug-report-form"] input[type="submit"]');

        $I->waitForElementVisible('[data-test="bug-report-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeElement('.alert-success');
        $I->seeInDatabase('technical_error_report', [
            'description' => $description,
            'reported_url' => $reportedUrl,
            'reporter_user_id' => self::ACCEPTANCE_ADMIN_USER_ID,
        ]);

        $reportId = $I->grabFromDatabase('technical_error_report', 'id', ['description' => $description]);
        $reporterEmail = $I->grabFromDatabase('technical_error_report', 'reporter_email', ['id' => $reportId]);
        Assert::assertNotEmpty($reporterEmail);
        if ($hasReporterEmailField) {
            Assert::assertSame('acceptance.reporter@example.test', $reporterEmail);
        }

        $diagnostics = (string) $I->grabFromDatabase('technical_error_report', 'diagnostics', ['id' => $reportId]);
        Assert::assertStringContainsString('"viewport"', $diagnostics);
        Assert::assertStringContainsString('"allRoles"', $diagnostics);

        $I->haveInDatabase('admin_user', [
            'user_id' => self::ACCEPTANCE_ADMIN_USER_ID,
            'created_at' => '2026-06-18 12:00:00',
        ]);

        $I->amOnPage('/admin/hlaseni-chyb');
        $I->waitForElementVisible('[data-test="admin-bug-reports-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeElement('[data-test="admin-bug-reports-grid"]');
        $I->see($description);
        $I->seeElement('[data-test="admin-nav-bug-reports"].btn-primary');
        $I->seeElement('[data-test="admin-bug-report-detail-grid"] svg[data-icon="eye"]');
        $I->seeElement('[data-test="admin-bug-report-resolve-grid"] svg[data-icon="circle-check"]');

        $I->amOnPage('/admin/hlaseni-chyb/'.$reportId);
        $I->waitForElementVisible('[data-test="admin-bug-report-detail"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->see($description);
        $I->see($reportedUrl);
        $I->seeElement('[data-test="admin-bug-report-diagnostics"]');
        $I->see('innerWidth', '[data-test="admin-bug-report-diagnostics"]');
        $I->seeElement('[data-test="admin-bug-report-diagnostics"].bug-report-diagnostics');
        $diagnosticsStyles = $I->executeJS(<<<'JS'
            const diagnostics = document.querySelector('[data-test="admin-bug-report-diagnostics"]');
            const styles = getComputedStyle(diagnostics);
            return {
                color: styles.color,
                backgroundColor: styles.backgroundColor,
                whiteSpace: styles.whiteSpace,
            };
            JS);
        Assert::assertSame('pre-wrap', $diagnosticsStyles['whiteSpace']);
        Assert::assertNotSame($diagnosticsStyles['backgroundColor'], $diagnosticsStyles['color']);
        $I->seeElement('[data-test="admin-bug-report-resolve"]');
        $I->seeElement('[data-test="admin-bug-report-reject"]');

        $replyMessage = 'Děkujeme za hlášení, doplňující odpověď ze systému.';
        $secondReplyMessage = 'Ještě doplňujeme další informace k hlášení.';
        $I->seeElement('[data-test="admin-bug-report-reply"]');
        $I->clickStable('[data-test="admin-bug-report-reply"]');
        $I->waitForElementVisible('#replyReport textarea[name="message"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->fillField('#replyReport textarea[name="message"]', $replyMessage);
        $I->click('#replyReport input[type="submit"]');
        $I->waitForElementVisible('[data-test="admin-bug-report-detail"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->see('Odpověď autorovi hlášení byla odeslána.');
        $I->seeElement('[data-test="admin-bug-report-sent-reply"]');
        $I->see($replyMessage, '[data-test="admin-bug-report-sent-reply"]');

        $I->clickStable('[data-test="admin-bug-report-reply"]');
        $I->waitForElementVisible('#replyReport textarea[name="message"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->fillField('#replyReport textarea[name="message"]', $secondReplyMessage);
        $I->click('#replyReport input[type="submit"]');
        $I->waitForElementVisible('[data-test="admin-bug-report-detail"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->see('Odpověď autorovi hlášení byla odeslána.');
        $I->see($replyMessage, '[data-test="admin-bug-report-sent-replies"]');
        $I->see($secondReplyMessage, '[data-test="admin-bug-report-sent-replies"]');
        $shownReplies = $I->executeJS(<<<'JS'
            return [...document.querySelectorAll('[data-test="admin-bug-report-sent-reply"]')]
                .map((reply) => reply.textContent.trim());
            JS);
        Assert::assertCount(2, $shownReplies);
        Assert::assertStringContainsString($secondReplyMessage, $shownReplies[0]);
        Assert::assertStringContainsString($replyMessage, $shownReplies[1]);
        Assert::assertNotNull(
            $I->grabFromDatabase('technical_error_report_reply', 'sent_at', [
                'report_id' => $reportId,
                'message' => $replyMessage,
            ]),
        );
        $I->seeInDatabase('technical_error_report_reply', [
            'report_id' => $reportId,
            'message' => $secondReplyMessage,
        ]);
        Assert::assertSame(
            2,
            (int) $I->grabNumRecords('technical_error_report_reply', ['report_id' => $reportId]),
        );

        $resolutionMessage = 'Nejde o technickou chybu aplikace.';
        $I->clickStable('[data-test="admin-bug-report-reject"]');
        $I->waitForElementVisible('#rejectReport textarea[name="message"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->fillField('#rejectReport textarea[name="message"]', $resolutionMessage);
        $I->click('#rejectReport input[type="submit"]');
        $I->waitForElementVisible('[data-test="admin-bug-reports-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->see('Hlášení technické chyby bylo zamítnuto');
        $I->dontSee($description, '[data-test="admin-bug-reports-grid"]');
        Assert::assertNotNull(
            $I->grabFromDatabase('technical_error_report', 'resolved_at', ['id' => $reportId]),
        );
        Assert::assertSame(
            'rejected',
            $I->grabFromDatabase('technical_error_report', 'resolution_state', ['id' => $reportId]),
        );
        Assert::assertSame(
            $resolutionMessage,
            $I->grabFromDatabase('technical_error_report', 'resolution_message', ['id' => $reportId]),
        );

        $I->amOnPage('/admin/hlaseni-chyb/'.$reportId);
        $I->waitForElementVisible('[data-test="admin-bug-reports-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->dontSeeElement('[data-test="admin-bug-report-detail"]');
    }
}
