<?php

declare(strict_types=1);

namespace App\Model\BugReport;

use App\Model\BugReport\Entity\TechnicalErrorReport;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Nette\Utils\Json;
use RuntimeException;
use Throwable;

use function array_filter;
use function array_values;
use function implode;
use function is_array;
use function is_int;
use function is_string;
use function sprintf;
use function trim;

final class GitHubIssueService
{
    private ?string $token;

    private ?string $owner;

    private ?string $repository;

    /** @var list<string> */
    private array $labels;

    /**
     * @param array{token?: string|null, owner?: string|null, repository?: string|null, labels?: list<string>} $config
     */
    public function __construct(
        private Client $client,
        array $config,
        private string $appBaseUrl,
    ) {
        $this->token = $this->normalizeNullableString($config['token'] ?? null);
        $this->owner = $this->normalizeNullableString($config['owner'] ?? null);
        $this->repository = $this->normalizeNullableString($config['repository'] ?? null);
        $this->labels = array_values(array_filter(
            $config['labels'] ?? [],
            static fn (mixed $label): bool => is_string($label) && trim($label) !== '',
        ));
    }

    public function isConfigured(): bool
    {
        return $this->token !== null
            && $this->owner !== null
            && $this->repository !== null;
    }

    public function createIssue(TechnicalErrorReport $report): GitHubIssue
    {
        $this->assertConfigured();

        $payload = [
            'title' => sprintf('Hlášení technické chyby #%d', $report->getId()),
            'body' => $this->createIssueBody($report),
        ];
        if ($this->labels !== []) {
            $payload['labels'] = $this->labels;
        }

        $data = $this->request('POST', '/issues', $payload);
        $number = $data['number'] ?? null;
        $url = $data['html_url'] ?? null;
        if (! is_int($number) || ! is_string($url) || $url === '') {
            throw new RuntimeException('GitHub returned an invalid issue response.');
        }

        return new GitHubIssue($number, $url);
    }

    public function addReplyComment(TechnicalErrorReport $report, string $message): GitHubIssueComment
    {
        $this->assertConfigured();

        $issueNumber = $report->getGitHubIssueNumber();
        if ($issueNumber === null) {
            throw new RuntimeException('Technical error report has no GitHub issue.');
        }

        $data = $this->request('POST', sprintf('/issues/%d/comments', $issueNumber), [
            'body' => $this->createReplyCommentBody($report, $message),
        ]);
        $url = $data['html_url'] ?? null;
        if (! is_string($url) || $url === '') {
            throw new RuntimeException('GitHub returned an invalid comment response.');
        }

        return new GitHubIssueComment($url);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $payload): array
    {
        try {
            $response = $this->client->request($method, $this->getApiBaseUrl().$path, [
                'headers' => [
                    'Accept' => 'application/vnd.github+json',
                    'Authorization' => 'Bearer '.$this->token,
                    'User-Agent' => 'skautske-hospodareni',
                    'X-GitHub-Api-Version' => '2022-11-28',
                ],
                'json' => $payload,
            ]);

            $data = Json::decode((string) $response->getBody(), Json::FORCE_ARRAY);
            if (! is_array($data)) {
                throw new RuntimeException('GitHub returned an invalid JSON response.');
            }

            return $data;
        } catch (GuzzleException $e) {
            throw new RuntimeException('GitHub API request failed: '.$e->getMessage(), previous: $e);
        } catch (Throwable $e) {
            if ($e instanceof RuntimeException) {
                throw $e;
            }

            throw new RuntimeException('GitHub API response could not be processed: '.$e->getMessage(), previous: $e);
        }
    }

    private function createIssueBody(TechnicalErrorReport $report): string
    {
        $role = array_filter([
            $report->getRoleName(),
            $report->getRoleId() !== null ? 'ID '.$report->getRoleId() : null,
        ]);
        $unit = array_filter([
            $report->getUnitName(),
            $report->getUnitId() !== null ? 'ID '.$report->getUnitId() : null,
        ]);

        return implode("\n", [
            'Byla nahlášena technická chyba ve Skautském hospodaření.',
            '',
            '## Hlášení',
            '- Interní ID: #'.$report->getId(),
            '- Administrace: '.$this->getAdminReportUrl($report),
            '- Nahlášeno: '.$report->getCreatedAt()->format('Y-m-d H:i:s P'),
            '- Uživatel: '.$report->getReporterDisplayName().' (ID '.$report->getReporterUserId().')',
            '- E-mail uživatele: '.($report->getReporterEmail() ?? '-'),
            '- Role: '.($role !== [] ? implode(', ', $role) : '-'),
            '- Jednotka: '.($unit !== [] ? implode(', ', $unit) : '-'),
            '- URL chyby: '.($report->getReportedUrl() ?? '-'),
            '- Release: '.$report->getAppRelease(),
            '- Screenshot: '.$this->formatScreenshotReference($report),
            '',
            '## Popis',
            $report->getDescription(),
            '',
            '## Diagnostika',
            '```json',
            Json::encode($report->getDiagnostics(), JSON_PRETTY_PRINT),
            '```',
        ]);
    }

    private function createReplyCommentBody(TechnicalErrorReport $report, string $message): string
    {
        return implode("\n", [
            'Admin odeslal uživateli odpověď ze systému.',
            '',
            'Interní hlášení: '.$this->getAdminReportUrl($report),
            '',
            '```',
            $message,
            '```',
        ]);
    }

    private function formatScreenshotReference(TechnicalErrorReport $report): string
    {
        if (! $report->hasScreenshot()) {
            return '-';
        }

        return sprintf(
            '%s (%s/admin/hlaseni-chyb/%d?do=downloadScreenshot)',
            $report->getScreenshotOriginalName() ?? 'screenshot',
            $this->appBaseUrl,
            $report->getId(),
        );
    }

    private function getAdminReportUrl(TechnicalErrorReport $report): string
    {
        return sprintf('%s/admin/hlaseni-chyb/%d', $this->appBaseUrl, $report->getId());
    }

    private function getApiBaseUrl(): string
    {
        return sprintf('https://api.github.com/repos/%s/%s', $this->owner, $this->repository);
    }

    private function assertConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('GitHub issue integration is not configured.');
        }
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
