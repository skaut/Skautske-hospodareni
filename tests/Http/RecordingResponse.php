<?php

declare(strict_types=1);

namespace Tests\Http;

use DateTimeInterface;
use Nette\Http\IResponse;

use function strtolower;

/**
 * HTTP odpověď pro testy, která si pamatuje nastavené hlavičky (v CLI je `header()` nečitelné).
 */
final class RecordingResponse implements IResponse
{
    private int $code = IResponse::S200_OK;

    /** @var array<string, string> */
    private array $headers = [];

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
        $this->headers[strtolower($name)] = $value;

        return $this;
    }

    public function addHeader(string $name, string $value): static
    {
        return $this->setHeader($name, $value);
    }

    public function getHeader(string $header): ?string
    {
        return $this->headers[strtolower($header)] ?? null;
    }

    /** @return array<string, string> */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setContentType(string $type, ?string $charset = null): static
    {
        return $this->setHeader('Content-Type', $type.($charset !== null ? '; charset='.$charset : ''));
    }

    public function setExpiration(?string $expire): static
    {
        return $this;
    }

    public function isSent(): bool
    {
        return false;
    }

    public function redirect(string $url, int $code = IResponse::S302_Found): void
    {
        $this->setCode($code);
        $this->setHeader('Location', $url);
    }

    public function setCookie(
        string $name,
        string $value,
        string|int|DateTimeInterface|null $expire,
        ?string $path = null,
        ?string $domain = null,
        ?bool $secure = null,
        ?bool $httpOnly = null,
    ): static {
        return $this;
    }

    public function deleteCookie(string $name, ?string $path = null, ?string $domain = null, ?bool $secure = null): static
    {
        return $this;
    }
}
