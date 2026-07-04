<?php

declare(strict_types=1);

namespace App;

use function filter_var;
use function inet_pton;
use function ip2long;
use function preg_match;
use function strpos;
use function substr;
use function trim;

use const FILTER_FLAG_IPV4;
use const FILTER_FLAG_IPV6;
use const FILTER_VALIDATE_IP;

final class MaintenanceMode
{
    /**
     * @param string[] $allowedIps
     */
    public static function isClientAllowed(string $clientIp, array $allowedIps): bool
    {
        if (filter_var($clientIp, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        foreach ($allowedIps as $allowedIp) {
            $allowedIp = trim($allowedIp);
            if ($allowedIp === '') {
                continue;
            }

            if ($clientIp === $allowedIp || self::matchesCidr($clientIp, $allowedIp)) {
                return true;
            }
        }

        return false;
    }

    private static function matchesCidr(string $clientIp, string $cidr): bool
    {
        if (strpos($cidr, '/') === false) {
            return false;
        }

        [$network, $prefixLength] = explode('/', $cidr, 2);
        if (! preg_match('~^\d{1,3}$~', $prefixLength)) {
            return false;
        }

        if (filter_var($clientIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            return self::matchesIpv4Cidr($clientIp, $network, (int) $prefixLength);
        }

        if (filter_var($clientIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            return self::matchesIpv6Cidr($clientIp, $network, (int) $prefixLength);
        }

        return false;
    }

    private static function matchesIpv4Cidr(string $clientIp, string $network, int $prefixLength): bool
    {
        if ($prefixLength < 0 || $prefixLength > 32 || filter_var($network, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            return false;
        }

        $client = ip2long($clientIp);
        $range = ip2long($network);
        if ($client === false || $range === false) {
            return false;
        }

        $mask = $prefixLength === 0 ? 0 : (-1 << (32 - $prefixLength));

        return ($client & $mask) === ($range & $mask);
    }

    private static function matchesIpv6Cidr(string $clientIp, string $network, int $prefixLength): bool
    {
        if ($prefixLength < 0 || $prefixLength > 128 || filter_var($network, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) {
            return false;
        }

        $client = inet_pton($clientIp);
        $range = inet_pton($network);
        if ($client === false || $range === false) {
            return false;
        }

        $bytes = intdiv($prefixLength, 8);
        $bits = $prefixLength % 8;

        if ($bytes > 0 && substr($client, 0, $bytes) !== substr($range, 0, $bytes)) {
            return false;
        }

        if ($bits === 0) {
            return true;
        }

        $mask = 0xFF << (8 - $bits);

        return (ord($client[$bytes]) & $mask) === (ord($range[$bytes]) & $mask);
    }

    /**
     * @param array<string, mixed> $server
     */
    public static function clientIpFromServer(array $server): string
    {
        $remoteAddress = $server['REMOTE_ADDR'] ?? '';

        return trim((string) $remoteAddress);
    }

    public static function enableDebugBypass(): void
    {
        putenv('APP_MAINTENANCE_BYPASS=1');
        putenv('TRACY_SHOW_BAR=true');
        $_ENV['APP_MAINTENANCE_BYPASS'] = '1';
        $_ENV['TRACY_SHOW_BAR'] = 'true';
        $_SERVER['APP_MAINTENANCE_BYPASS'] = '1';
        $_SERVER['TRACY_SHOW_BAR'] = 'true';
    }
}
