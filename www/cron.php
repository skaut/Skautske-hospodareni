<?php

declare(strict_types=1);

use App\CronToken;
use App\Environment;
use App\Model\Infrastructure\Scheduler\CronJobResult;
use App\Model\Infrastructure\Scheduler\CronRunner;
use Nette\DI\Container;

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../app/Environment.php';
require_once __DIR__.'/../app/CronToken.php';

header('Content-Type: text/plain; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo "405 Method Not Allowed\n";

    exit;
}

Environment::load(dirname(__DIR__));

if (! CronToken::isAuthorized((string) getenv('CRON_TOKEN_SHA256'), $_SERVER, $_POST)) {
    http_response_code(403);
    echo "403 Forbidden\n";

    exit;
}

if (Environment::getConfiguration()['maintenance']['enabled']) {
    http_response_code(503);
    header('Retry-After: 600');
    echo "503 Service Unavailable: maintenance mode\n";

    exit;
}

// The external caller may stop waiting, the jobs still have to finish.
ignore_user_abort(true);
set_time_limit(300);

$container = require __DIR__.'/../app/bootstrap.php';

if (! $container instanceof Container) {
    http_response_code(500);
    echo "Unable to bootstrap application container.\n";

    exit;
}

$results = $container->getByType(CronRunner::class)->runDueJobs(new DateTime());

http_response_code(in_array(CronJobResult::FAILED, $results, true) ? 500 : 200);

if ($results === []) {
    echo "No job is due.\n";
}

foreach ($results as $name => $result) {
    echo $name.': '.$result->value."\n";
}

exit;
