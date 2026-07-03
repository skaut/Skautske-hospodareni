<?php

declare(strict_types=1);

use App\Environment;
use Doctrine\Migrations\Tools\Console\Command\MigrateCommand;
use Nette\DI\Container;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../app/Environment.php';

header('Content-Type: text/plain; charset=utf-8');

Environment::load(dirname(__DIR__));

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

$container = require __DIR__.'/../app/bootstrap.php';

if (! $container instanceof Container) {
    http_response_code(500);
    echo "Unable to bootstrap application container.\n";

    exit;
}

$command = $container->getService('migrations.migrateCommand');

if (! $command instanceof MigrateCommand) {
    http_response_code(500);
    echo "Unable to initialize migrate command.\n";

    exit;
}

$input = new ArrayInput([]);
$input->setInteractive(false);
$output = new BufferedOutput();
$exitCode = $command->run($input, $output);
$contents = $output->fetch();

http_response_code($exitCode === 0 ? 200 : 500);

if ($contents === '') {
    echo $exitCode === 0 ? "Migrations completed.\n" : "Migration command failed.\n";
} else {
    echo $contents;
}

if ($exitCode !== 0) {
    exit($exitCode);
}

exit;
