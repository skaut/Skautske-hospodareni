<?php

declare(strict_types=1);

namespace App\Http;

use Nette\Application\Response;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Psr\Http\Message\StreamInterface;

/**
 * Uložený soubor (sken, příloha) streamovaný klientovi.
 *
 * Inline smí jen PDF – cokoli jiného se vždy vrátí jako příloha ke stažení, aby nahraný soubor nešlo
 * podstrčit prohlížeči k vykreslení pod doménou aplikace.
 */
final class FileStreamResponse implements Response
{
    public function __construct(
        private StreamInterface $stream,
        private string $filename,
        private string $contentType,
        private bool $download = true,
    ) {
    }

    public function isInline(): bool
    {
        return ! $this->download && $this->contentType === PdfResponse::ContentType;
    }

    public function send(IRequest $httpRequest, IResponse $httpResponse): void
    {
        $size = $this->stream->getSize();
        if ($size !== null) {
            $httpResponse->setHeader('Content-Length', (string) $size);
        }

        $httpResponse->setHeader('Content-Type', $this->contentType);
        $httpResponse->setHeader(
            'Content-Disposition',
            $this->isInline() ? ContentDisposition::inline($this->filename) : ContentDisposition::attachment($this->filename),
        );
        $httpResponse->setHeader('X-Content-Type-Options', 'nosniff');

        while (! $this->stream->eof()) {
            echo $this->stream->read((int) 4e6);
        }

        $this->stream->close();
    }
}
