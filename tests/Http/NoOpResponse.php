<?php

declare(strict_types=1);

namespace Tests\Http;

use AllowDynamicProperties;
use DateTimeInterface;
use Nette\Http\IResponse;
use Nette\Http\SameSite;

#[AllowDynamicProperties]
final class NoOpResponse implements IResponse
{
    private int $code = IResponse::S200_OK;

    public function setCode(int $code, ?string $reason = null): static
    {
        $this->code = $code;

        return $this;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function setHeader(string $name, string $value): static
    {
        return $this;
    }

    public function addHeader(string $name, string $value): static
    {
        return $this;
    }

    public function deleteHeader(string $name): static
    {
        return $this;
    }

    public function getHeader(string $header): ?string
    {
        return null;
    }

    /** @return array<string|string> */
    public function getHeaders(): array
    {
        return [];
    }

    public function setExpiration(?string $expire): static
    {
        return $this;
    }

    public function isSent(): bool
    {
        return false;
    }

    public function setContentType(string $type, ?string $charset = null): static
    {
        return $this;
    }

    public function redirect(string $url, int $code = IResponse::S302_Found): void
    {
        /* no-op */
    }

    public function setCookie(string $name, string $value, string|int|DateTimeInterface|null $expire, ?string $path = null, ?string $domain = null, ?bool $secure = null, ?bool $httpOnly = null, SameSite|string|null $sameSite = null, bool $partitioned = false): static
    {
        return $this;
    }

    public function deleteCookie(string $name, ?string $path = null, ?string $domain = null, ?bool $secure = null): static
    {
        return $this;
    }
}
