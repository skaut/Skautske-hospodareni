<?php

declare(strict_types=1);

namespace App\Model\BugReport;

use App\Model\BugReport\Entity\TechnicalErrorReport;
use App\Model\BugReport\Manager\TechnicalErrorReportManager;
use App\Model\User\SkautisRole;
use App\Model\User\UserService;
use Nette\Http\IRequest;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;
use Nette\Utils\Validators;
use RuntimeException;
use stdClass;
use Throwable;

use function array_keys;
use function array_map;
use function get_class;
use function get_object_vars;
use function is_array;
use function is_numeric;
use function is_string;
use function sprintf;
use function trim;

final class BugReportService
{
    public function __construct(
        private TechnicalErrorReportManager $manager,
        private BugReportNotificationService $notificationService,
        private User $user,
        private UserService $userService,
        private IRequest $httpRequest,
        private string $appRelease,
    ) {
    }

    /** @param array<string, mixed> $clientDiagnostics */
    public function submit(string $description, ?string $reportedUrl, array $clientDiagnostics): TechnicalErrorReport
    {
        $identity = $this->user->getIdentity();
        if (! $identity instanceof SimpleIdentity || ! is_numeric($identity->getId())) {
            throw new RuntimeException('A technical error report requires an authenticated user.');
        }

        $userId = (int) $identity->getId();
        $currentRole = $identity->currentRole ?? null;
        if (! $currentRole instanceof SkautisRole) {
            $currentRole = null;
        }

        $diagnosticErrors = [];
        $userDetail = [];

        try {
            $userDetail = get_object_vars($this->userService->getUserDetail());
        } catch (Throwable $e) {
            $diagnosticErrors[] = $this->formatDiagnosticError('userDetail', $e);
        }

        try {
            $roleId = $this->userService->getRoleId();
        } catch (Throwable $e) {
            $roleId = null;
            $diagnosticErrors[] = $this->formatDiagnosticError('roleId', $e);
        }

        $displayName = $this->resolveDisplayName($userDetail, $userId);
        $email = $this->resolveEmail($userDetail);
        $userAgent = $this->httpRequest->getHeader('User-Agent');
        $access = $identity->access ?? [];
        if (! is_array($access)) {
            $access = [];
        }
        $diagnostics = [
            'user' => [
                'id' => $userId,
                'detail' => $userDetail,
                'activeRole' => $currentRole === null ? null : [
                    'id' => $roleId,
                    'name' => $currentRole->getName(),
                    'unitId' => $currentRole->getUnitId(),
                    'unitName' => $currentRole->getUnitName(),
                ],
                'allRoles' => array_map(
                    static fn (mixed $role): mixed => $role instanceof stdClass ? get_object_vars($role) : $role,
                    $identity->getRoles(),
                ),
                'accessibleUnitIds' => [
                    UserService::ACCESS_READ => $this->unitIdsFromAccess($access[UserService::ACCESS_READ] ?? []),
                    UserService::ACCESS_EDIT => $this->unitIdsFromAccess($access[UserService::ACCESS_EDIT] ?? []),
                ],
            ],
            'request' => [
                'url' => (string) $this->httpRequest->getUrl(),
                'method' => $this->httpRequest->getMethod(),
                'remoteAddress' => $this->httpRequest->getRemoteAddress(),
                'headers' => $this->collectSafeHeaders(),
            ],
            'application' => [
                'release' => $this->appRelease,
                'phpVersion' => PHP_VERSION,
                'phpSapi' => PHP_SAPI,
            ],
            'browser' => $clientDiagnostics,
            'collectionErrors' => $diagnosticErrors,
        ];

        $report = $this->manager->create(new TechnicalErrorReport(
            trim($description),
            $reportedUrl !== null && trim($reportedUrl) !== '' ? trim($reportedUrl) : null,
            $userId,
            $displayName,
            $email,
            $roleId,
            $currentRole?->getName(),
            $currentRole?->getUnitId(),
            $currentRole?->getUnitName(),
            $this->httpRequest->getRemoteAddress(),
            $userAgent,
            $this->appRelease,
            $diagnostics,
        ));

        try {
            $this->notificationService->notify($report);
            $report->markNotificationSent();
        } catch (Throwable $e) {
            $report->markNotificationFailed($e->getMessage());
        }

        $this->manager->saveNotificationState($report);

        return $report;
    }

    /** @param array<string, mixed> $userDetail */
    private function resolveDisplayName(array $userDetail, int $userId): string
    {
        foreach (['Person', 'DisplayName', 'Name'] as $key) {
            if (isset($userDetail[$key]) && is_string($userDetail[$key]) && trim($userDetail[$key]) !== '') {
                return $userDetail[$key];
            }
        }

        return 'Uživatel '.$userId;
    }

    /** @param array<string, mixed> $userDetail */
    private function resolveEmail(array $userDetail): ?string
    {
        foreach (['Email', 'PersonEmail', 'UserEmail', 'Mail'] as $key) {
            $email = $userDetail[$key] ?? null;
            if (! is_string($email)) {
                continue;
            }

            $email = trim($email);
            if ($email !== '' && Validators::isEmail($email)) {
                return $email;
            }
        }

        return null;
    }

    /** @return array<string, string> */
    private function collectSafeHeaders(): array
    {
        $headers = [];

        foreach ([
            'Accept-Language',
            'Referer',
            'User-Agent',
            'Sec-CH-UA',
            'Sec-CH-UA-Mobile',
            'Sec-CH-UA-Platform',
            'DNT',
            'X-Forwarded-For',
            'X-Request-ID',
            'Traceparent',
        ] as $headerName) {
            $value = $this->httpRequest->getHeader($headerName);
            if ($value !== null && $value !== '') {
                $headers[$headerName] = $value;
            }
        }

        return $headers;
    }

    private function formatDiagnosticError(string $source, Throwable $error): string
    {
        return sprintf('%s: %s: %s', $source, get_class($error), $error->getMessage());
    }

    /** @return int[] */
    private function unitIdsFromAccess(mixed $access): array
    {
        if (! is_array($access)) {
            return [];
        }

        return array_map('intval', array_keys($access));
    }
}
