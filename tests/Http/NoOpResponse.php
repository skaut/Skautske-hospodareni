<?php

declare(strict_types=1);

namespace Tests\Http;

use DateTimeInterface;
use Nette\Http\IResponse;

#[\AllowDynamicProperties]   // ← přidat
final class NoOpResponse implements IResponse
{
    private int $code = IResponse::S200_OK;

    public function setCode(int $code, string|null $reason = null): static
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

    public function getHeader(string $header): string|null
    {
        return null;
    }

    /** @return array<string|string> */
    public function getHeaders(): array
    {
        return [];
    }

    public function setExpiration(string|null $expire): static
    {
        return $this;
    }

    public function isSent(): bool
    {
        return false;
    }

    public function setContentType(string $type, string|null $charset = null): static
    {
        return $this;
    }

    public function redirect(string $url, int $code = IResponse::S302_Found): void
    {
 /* no-op */
    }

    /**
     *  @param string|int|DateTimeInterface $expire time, value null means "until the browser session ends"
     *
     * @return $this
     */
    public function setCookie(string $name, string $value, $expire, string|null $path = null, string|null $domain = null, bool|null $secure = null, bool|null $httpOnly = null, string|null $sameSite = null): static
    {
        return $this;
    }

    public function deleteCookie(string $name, string|null $path = null, string|null $domain = null, bool|null $secure = null): static
    {
        return $this;
    }
}
