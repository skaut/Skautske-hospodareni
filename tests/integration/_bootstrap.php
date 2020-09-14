<?php

declare(strict_types=1);

use VCR\VCR;

VCR::configure()
    ->enableLibraryHooks(['soap'])
    ->setCassettePath(__DIR__ . '/fixtures')
    ->setMode(VCR::MODE_NONE)
    ->setStorage('json');

VCR::turnOn();
