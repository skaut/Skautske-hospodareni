<?php

declare(strict_types=1);

namespace App;

use RuntimeException;

use function array_fill_keys;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_values;
use function explode;
use function file_get_contents;
use function getenv;
use function in_array;
use function is_file;
use function preg_match;
use function putenv;
use function rtrim;
use function str_contains;
use function str_ends_with;
use function str_replace;
use function str_starts_with;
use function strpos;
use function strtolower;
use function substr;
use function trim;

final class Environment
{
    private static bool $loaded = false;

    private static ?string $projectDir = null;

    /** @var array<string, mixed>|null */
    private static ?array $configuration = null;

    public static function load(string $projectDir): void
    {
        if (self::$loaded && self::$projectDir === $projectDir) {
            return;
        }

        self::$projectDir = $projectDir;

        /** @var array<string, bool> $protectedVariables */
        $protectedVariables = array_fill_keys(array_keys(getenv()), true);

        self::loadFile($projectDir.'/.env', $protectedVariables);

        if (self::getString('APP_ENV', 'dev') !== 'ci') {
            self::loadFile($projectDir.'/.env.local', $protectedVariables);
        }

        $appEnv = self::getString('APP_ENV', 'dev');
        self::loadFile($projectDir.'/.env.'.$appEnv, $protectedVariables);
        self::loadFile($projectDir.'/.env.'.$appEnv.'.local', $protectedVariables);

        self::setVariable('APP_ENV', self::getString('APP_ENV', 'dev'));
        self::$configuration = self::buildConfiguration($projectDir);
        self::$loaded = true;
    }

    /** @return array<string, mixed> */
    public static function getConfiguration(): array
    {
        if (! self::$loaded || self::$configuration === null) {
            throw new RuntimeException('Environment has not been loaded yet.');
        }

        return self::$configuration;
    }

    /**
     * @param array<string, bool> $protectedVariables
     */
    private static function loadFile(string $path, array $protectedVariables): void
    {
        if (! is_file($path)) {
            return;
        }

        $variables = self::parseFile((string) file_get_contents($path));

        foreach ($variables as $name => $value) {
            if (array_key_exists($name, $protectedVariables)) {
                continue;
            }

            self::setVariable($name, $value);
        }
    }

    /** @return array<string, string> */
    private static function parseFile(string $contents): array
    {
        $variables = [];

        foreach (explode("\n", str_replace("\r\n", "\n", $contents)) as $line) {
            $trimmedLine = trim($line);

            if ($trimmedLine === '' || str_starts_with($trimmedLine, '#')) {
                continue;
            }

            if (str_starts_with($trimmedLine, 'export ')) {
                $trimmedLine = trim(substr($trimmedLine, 7));
            }

            $separatorPosition = strpos($trimmedLine, '=');
            if ($separatorPosition === false) {
                continue;
            }

            $name = trim(substr($trimmedLine, 0, $separatorPosition));
            $value = trim(substr($trimmedLine, $separatorPosition + 1));

            if ($name === '') {
                continue;
            }

            $variables[$name] = self::normalizeValue($value);
        }

        return $variables;
    }

    private static function normalizeValue(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"'))
            || (str_starts_with($value, '\'') && str_ends_with($value, '\''))
        ) {
            $quote = $value[0];
            $unquotedValue = substr($value, 1, -1);

            if ($quote === '"') {
                return str_replace(['\\"', '\\n', '\\r', '\\t', '\\\\'], ['"', "\n", "\r", "\t", '\\'], $unquotedValue);
            }

            return str_replace(['\\\'', '\\\\'], ['\'', '\\'], $unquotedValue);
        }

        if (str_contains($value, ' #')) {
            $value = trim((string) strtok($value, '#'));
        }

        return $value;
    }

    /** @return array<string, mixed> */
    private static function buildConfiguration(string $projectDir): array
    {
        $appEnv = self::getString('APP_ENV', 'dev');
        $baseUrl = rtrim(self::getString('APP_BASE_URL', 'http://moje-hospodareni.cz'), '/');
        $googleCredentialsFile = self::getString(
            'GOOGLE_CREDENTIALS_FILE',
            $appEnv === 'ci' ? 'ci-google-credentials.json' : 'google-credentials.json',
        );

        return [
            'appEnv' => $appEnv,
            'appBaseUrl' => $baseUrl,
            'sendEmail' => self::getBool('SEND_EMAIL', $appEnv !== 'dev'),
            'errorEmails' => self::getList('ERROR_EMAILS'),
            'testBackground' => self::getBool('TEST_BACKGROUND', $appEnv !== 'prod'),
            'tracyShowBar' => self::getBool('TRACY_SHOW_BAR', $appEnv === 'dev'),
            'database' => [
                'host' => self::requireString('DB_HOST'),
                'user' => self::requireString('DB_USER'),
                'password' => self::requireString('DB_PASSWORD'),
                'name' => self::requireString('DB_NAME'),
            ],
            'google' => [
                'credentialsPath' => self::resolvePath($projectDir, $googleCredentialsFile),
                'redirectUri' => self::getString('GOOGLE_REDIRECT_URI', $baseUrl.'/google/token'),
            ],
            'skautis' => [
                'applicationId' => self::requireString('APPLICATION_ID'),
                'testMode' => self::getBool('SKAUTIS_TEST_MODE', $appEnv !== 'prod'),
            ],
            'sentry' => [
                'dsn' => self::getNullableString('SENTRY_DSN'),
                'releaseHash' => self::getString('APP_RELEASE_HASH', 'dev'),
            ],
        ];
    }

    private static function resolvePath(string $projectDir, string $path): string
    {
        if (str_starts_with($path, '/') || preg_match('~^[A-Za-z]:[\\\\/]~', $path) === 1) {
            return $path;
        }

        return $projectDir.'/app/config/'.$path;
    }

    private static function requireString(string $name): string
    {
        $value = self::getNullableString($name);

        if ($value === null) {
            throw new RuntimeException('Missing required environment variable '.$name.'.');
        }

        return $value;
    }

    private static function getString(string $name, string $default = ''): string
    {
        return self::getNullableString($name) ?? $default;
    }

    private static function getNullableString(string $name): ?string
    {
        $value = getenv($name);

        if ($value === false || $value === '') {
            return null;
        }

        return $value;
    }

    private static function getBool(string $name, bool $default = false): bool
    {
        $value = self::getNullableString($name);

        if ($value === null) {
            return $default;
        }

        $normalized = strtolower($value);

        if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
            return true;
        }

        if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
            return false;
        }

        return $default;
    }

    /** @return string[] */
    private static function getList(string $name): array
    {
        $value = self::getNullableString($name);
        if ($value === null) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }

    private static function setVariable(string $name, string $value): void
    {
        putenv($name.'='.$value);
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}
