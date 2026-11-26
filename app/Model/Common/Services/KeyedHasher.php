<?php

declare(strict_types=1);

namespace App\Model\Common\Services;

use function hash_hmac;

/**
 * One-way hash with the secret key of the application (APP_KEY), e.g. to count distinct users in the
 * statistics without keeping who they were.
 *
 * A keyed hash on purpose: a plain hash of a small number is reversed by hashing every possible value. The key
 * is not in the database, so stored hashes alone never lead back to the value. Each use names its purpose, so
 * the same value hashed for two purposes gives two unrelated hashes.
 */
final class KeyedHasher
{
    /** Length of a hash in hex. */
    public const LENGTH = 64;

    public function __construct(private ?string $secret)
    {
    }

    public function isConfigured(): bool
    {
        return $this->secret !== null && $this->secret !== '';
    }

    /** @return string|null null when the key is not configured */
    public function hash(string $purpose, string|int $value): ?string
    {
        if ($this->secret === null || $this->secret === '') {
            return null;
        }

        return hash_hmac('sha256', $purpose."\n".$value, $this->secret);
    }
}
