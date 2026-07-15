<?php

declare(strict_types=1);

namespace App\Model\BugReport;

use App\Model\BugReport\Entity\TechnicalErrorReport;
use Codeception\Test\Unit;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use ReflectionProperty;
use RuntimeException;

use function json_decode;

final class GitHubIssueServiceTest extends Unit
{
    public function testCreateIssueSendsReportToGitHub(): void
    {
        $history = [];
        $service = $this->createService([
            new Response(201, [], '{"number":42,"html_url":"https://github.com/skaut/hospodareni/issues/42"}'),
        ], $history);

        $issue = $service->createIssue($this->createReport());

        self::assertSame(42, $issue->getNumber());
        self::assertSame('https://github.com/skaut/hospodareni/issues/42', $issue->getUrl());
        self::assertCount(1, $history);

        $request = $history[0]['request'];
        self::assertInstanceOf(RequestInterface::class, $request);
        self::assertSame('POST', $request->getMethod());
        self::assertSame('https://api.github.com/repos/skaut/hospodareni/issues', (string) $request->getUri());
        self::assertSame('Bearer test-token', $request->getHeaderLine('Authorization'));

        $payload = json_decode((string) $request->getBody(), true);
        self::assertSame('Hlášení technické chyby ze Skautského hospodaření', $payload['title']);
        self::assertSame(['bug', 'user-report'], $payload['labels']);
        self::assertStringContainsString('Automatické issue ze Skautského hospodaření.', $payload['body']);
        self::assertStringContainsString('Nefunguje export.', $payload['body']);
        self::assertStringNotContainsString('Interní ID', $payload['body']);
        self::assertStringNotContainsString('Administrace', $payload['body']);
        self::assertStringNotContainsString('E-mail uživatele', $payload['body']);
        self::assertStringNotContainsString('Role', $payload['body']);
        self::assertStringNotContainsString('Jednotka', $payload['body']);
        self::assertStringNotContainsString('Screenshot', $payload['body']);
        self::assertStringNotContainsString('jana.kvapilova@example.test', $payload['body']);
        self::assertStringNotContainsString('Středisko: správce akcí', $payload['body']);
        self::assertStringNotContainsString('středisko Pozořice', $payload['body']);
        self::assertStringNotContainsString('screenshot.jpg', $payload['body']);
        self::assertStringNotContainsString('Diagnostika', $payload['body']);
        self::assertStringNotContainsString('```json', $payload['body']);
        $this->assertPayloadDoesNotContainAdminLinks($payload);
    }

    public function testAddReplyCommentSendsCommentToExistingIssue(): void
    {
        $history = [];
        $service = $this->createService([
            new Response(201, [], '{"html_url":"https://github.com/skaut/hospodareni/issues/42#issuecomment-1"}'),
        ], $history);
        $report = $this->createReport();
        $report->markGitHubIssueCreated(42, 'https://github.com/skaut/hospodareni/issues/42');

        $comment = $service->addReplyComment($report, 'Prosíme o doplnění času chyby.');

        self::assertSame('https://github.com/skaut/hospodareni/issues/42#issuecomment-1', $comment->getUrl());
        self::assertCount(1, $history);

        $request = $history[0]['request'];
        self::assertInstanceOf(RequestInterface::class, $request);
        self::assertSame(
            'https://api.github.com/repos/skaut/hospodareni/issues/42/comments',
            (string) $request->getUri(),
        );

        $payload = json_decode((string) $request->getBody(), true);
        self::assertStringContainsString('Admin odeslal uživateli odpověď ze systému.', $payload['body']);
        self::assertStringContainsString('Prosíme o doplnění času chyby.', $payload['body']);
        self::assertStringNotContainsString('Interní hlášení', $payload['body']);
        $this->assertPayloadDoesNotContainAdminLinks($payload);
    }

    public function testUnconfiguredServiceRefusesToCreateIssue(): void
    {
        $service = new GitHubIssueService(
            new Client(['handler' => HandlerStack::create(new MockHandler())]),
            [],
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('GitHub issue integration is not configured.');

        $service->createIssue($this->createReport());
    }

    /**
     * @param list<Response>             $responses
     * @param list<array<string, mixed>> $history
     */
    private function createService(array $responses, array &$history): GitHubIssueService
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push(Middleware::history($history));

        return new GitHubIssueService(
            new Client(['handler' => $handlerStack]),
            [
                'token' => 'test-token',
                'owner' => 'skaut',
                'repository' => 'hospodareni',
                'labels' => ['bug', 'user-report'],
            ],
        );
    }

    private function createReport(): TechnicalErrorReport
    {
        $report = new TechnicalErrorReport(
            'Nefunguje export.',
            'https://h.skauting.cz/akce/123',
            1882,
            'Jana Kvapilová',
            'jana.kvapilova@example.test',
            117123,
            'Středisko: správce akcí - 623.21',
            23378,
            'středisko Pozořice',
            '127.0.0.1',
            'Test browser',
            'test-release',
            ['browser' => ['viewport' => ['innerWidth' => 1200]]],
            screenshotPath: 'bug-reports/screenshot.jpg',
            screenshotOriginalName: 'screenshot.jpg',
            screenshotContentType: 'image/jpeg',
            screenshotSize: 12,
        );

        $id = new ReflectionProperty($report, 'id');
        $id->setAccessible(true);
        $id->setValue($report, 123);

        return $report;
    }

    /** @param array<string, mixed> $payload */
    private function assertPayloadDoesNotContainAdminLinks(array $payload): void
    {
        $body = (string) ($payload['body'] ?? '');

        self::assertStringNotContainsString('/admin/', $body);
        self::assertStringNotContainsString('admin/hlaseni-chyb', $body);
        self::assertStringNotContainsString('https://h.skauting.cz/admin/hlaseni-chyb/123', $body);
        self::assertStringNotContainsString('downloadScreenshot', $body);
    }
}
