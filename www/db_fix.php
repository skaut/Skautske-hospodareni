<?php

declare(strict_types=1);

use function ctype_xdigit;
use function escapeshellarg;
use function getenv;
use function hash_equals;
use function http_response_code;
use function is_resource;
use function sprintf;
use function strlen;
use function trim;

header('Content-Type: text/plain; charset=utf-8');

$configuredToken = trim((string) getenv('DB_FIX_TOKEN_SHA256'));
$providedToken = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));

if (
    $configuredToken === ''
    || strlen($configuredToken) !== 64
    || ! ctype_xdigit($configuredToken)
    || strlen($providedToken) !== 64
    || ! ctype_xdigit($providedToken)
    || ! hash_equals(strtolower($configuredToken), strtolower($providedToken))
) {
    http_response_code(403);
    echo "403 Forbidden\n";

    exit;
}

$projectRoot = dirname(__DIR__);
$command = sprintf(
    '%s %s migrations:migrate --no-interaction 2>&1',
    escapeshellarg(PHP_BINARY),
    escapeshellarg($projectRoot . '/bin/console'),
);

$descriptors = [
    1 => ['pipe', 'w'],
];

$process = proc_open($command, $descriptors, $pipes, $projectRoot);

if (! is_resource($process)) {
    http_response_code(500);
    echo "Unable to start migrations.\n";

    exit;
}

$output = stream_get_contents($pipes[1]);
fclose($pipes[1]);

$exitCode = proc_close($process);

http_response_code($exitCode === 0 ? 200 : 500);

echo $output;

