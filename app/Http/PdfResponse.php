<?php

declare(strict_types=1);

namespace App\Http;

use Nette\Application\Response;
use Nette\Http\IRequest;
use Nette\Http\IResponse;

use function strlen;

/**
 * Vygenerované PDF: ke stažení (attachment) nebo k zobrazení v prohlížeči (inline).
 */
final class PdfResponse implements Response
{
    public const ContentType = 'application/pdf';

    public function __construct(
        private string $content,
        private string $filename,
        private bool $download = false,
    ) {
    }

    public function send(IRequest $httpRequest, IResponse $httpResponse): void
    {
        $httpResponse->setContentType(self::ContentType);
        $httpResponse->setHeader(
            'Content-Disposition',
            $this->download ? ContentDisposition::attachment($this->filename) : ContentDisposition::inline($this->filename),
        );
        $httpResponse->setHeader('Content-Length', (string) strlen($this->content));
        $httpResponse->setHeader('Cache-Control', 'no-store, max-age=0');
        $httpResponse->setHeader('X-Content-Type-Options', 'nosniff');

        echo $this->content;
    }
}
