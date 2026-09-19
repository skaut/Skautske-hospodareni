<?php

declare(strict_types=1);

use Nette\Utils\FileSystem;
use VCR\VCR;

$cassettePath = __DIR__.'/../../temp/vcr-fixtures';
FileSystem::copy(__DIR__.'/fixtures', $cassettePath);

VCR::configure()
    ->enableLibraryHooks(['soap'])
    ->setCassettePath($cassettePath)
    ->setMode(VCR::MODE_NONE)
    ->setStorage('json');

VCR::turnOn();
