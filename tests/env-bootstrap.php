<?php

declare(strict_types=1);

use App\Environment;

require_once __DIR__.'/../app/Environment.php';

/**
 * Loads test environment variables using the same loader as the application.
 *
 * @return array<string, mixed>
 */
function loadTestEnvironmentConfiguration(): array
{
    putenv('APP_ENV=test');
    $_ENV['APP_ENV'] = 'test';
    $_SERVER['APP_ENV'] = 'test';

    $projectDir = dirname(__DIR__);

    Environment::load($projectDir);

    return Environment::getConfiguration();
}
