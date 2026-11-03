<?php

declare(strict_types=1);

namespace App;

use function hash;
use function hash_equals;
use function is_string;
use function preg_match;
use function strlen;
use function strtolower;
use function trim;

/**
 * Authorizes calls of www/cron.php. The environment keeps only the SHA-256 hash of the token.
 */
final class CronToken
{
    private const MIN_LENGTH = 32;

    /**
     * @param array<mixed> $server
     * @param array<mixed> $post
     */
    public static function isAuthorized(string $configuredHash, array $server, array $post): bool
    {
        $configuredHash = strtolower(trim($configuredHash));
        $token = self::fromRequest($server, $post);

        return preg_match('~^[0-9a-f]{64}$~D', $configuredHash) === 1
            && strlen($token) >= self::MIN_LENGTH
            && hash_equals($configuredHash, hash('sha256', $token));
    }

    /**
     * Shared hostings may drop the Authorization header, so the token can come in the POST body too.
     *
     * @param array<mixed> $server
     * @param array<mixed> $post
     */
    private static function fromRequest(array $server, array $post): string
    {
        $authorization = $server['HTTP_AUTHORIZATION'] ?? $server['REDIRECT_HTTP_AUTHORIZATION'] ?? null;

        if (is_string($authorization) && preg_match('~^Bearer\s+(\S+)$~iD', trim($authorization), $matches) === 1) {
            return $matches[1];
        }

        $token = $post['token'] ?? null;

        return is_string($token) ? trim($token) : '';
    }
}
