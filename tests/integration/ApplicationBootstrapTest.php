<?php

declare(strict_types=1);

namespace App;

use Codeception\Test\Unit;

use function dirname;
use function escapeshellarg;
use function exec;
use function implode;
use function json_decode;
use function sprintf;
use function var_export;

use const JSON_THROW_ON_ERROR;
use const PHP_BINARY;

/**
 * Aplikace musí nastartovat přes app/bootstrap.php i v debug režimu, kde RobotLoader při startu
 * prochází app/. Bootstrap běží v čerstvém PHP procesu: v procesu testů jsou třídy Nette už
 * načtené, takže by se tam chyba v pořadí autoloaderů neprojevila.
 */
final class ApplicationBootstrapTest extends Unit
{
    public function testBootstrapCreatesContainerInDebugMode(): void
    {
        $bootstrap = dirname(__DIR__, 2).'/app/bootstrap.php';
        $script = sprintf(
            '$container = require %s; echo json_encode(["container" => $container instanceof Nette\DI\Container, "debugMode" => $container->getParameter("debugMode")]);',
            var_export($bootstrap, true),
        );

        // APP_MAINTENANCE_BYPASS zapne debug režim bez přepnutí na vývojovou konfiguraci.
        exec(
            sprintf('APP_MAINTENANCE_BYPASS=1 %s -r %s 2>&1', escapeshellarg(PHP_BINARY), escapeshellarg($script)),
            $output,
            $exitCode,
        );

        $result = implode("\n", $output);
        self::assertSame(0, $exitCode, 'Bootstrap failed: '.$result);
        self::assertSame(
            ['container' => true, 'debugMode' => true],
            json_decode($result, true, 512, JSON_THROW_ON_ERROR),
        );
    }
}
