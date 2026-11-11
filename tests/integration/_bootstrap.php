<?php

declare(strict_types=1);

use Nette\Utils\FileSystem;
use VCR\Request;
use VCR\VCR;

$cassettePath = __DIR__.'/../../temp/vcr-fixtures';
FileSystem::copy(__DIR__.'/fixtures', $cassettePath);

// skautis/skautis 3 sends an empty <env:Header/>, the cassettes were recorded without it.
$withoutEmptySoapHeader = static fn (?string $body): ?string => $body === null ? null : str_replace('<env:Header/>', '', $body);

VCR::configure()
    ->enableLibraryHooks(['soap'])
    ->setCassettePath($cassettePath)
    ->setMode(VCR::MODE_NONE)
    ->setStorage('json')
    ->addRequestMatcher(
        'body',
        static fn (Request $stored, Request $request): bool => $withoutEmptySoapHeader($stored->getBody()) === $withoutEmptySoapHeader($request->getBody()),
    );

VCR::turnOn();
