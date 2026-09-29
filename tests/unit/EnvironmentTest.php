<?php

declare(strict_types=1);

namespace App;

use Codeception\Test\Unit;
use Nette\Utils\FileSystem;
use RuntimeException;

use function dirname;
use function getenv;
use function is_array;
use function putenv;
use function sys_get_temp_dir;
use function uniqid;

/**
 * Environment čte .env soubory a staví z nich konfiguraci aplikace.
 *
 * Test pracuje s reálnými soubory v temp adresáři a reálnými proměnnými prostředí; původní stav
 * procesu si po sobě vrací, protože ostatní testy běží ve stejném procesu.
 */
final class EnvironmentTest extends Unit
{
    /** @var array<string, string> */
    private array $originalEnvironment = [];

    private string $workDir;

    protected function _before(): void
    {
        $environment = getenv();
        $this->originalEnvironment = is_array($environment) ? $environment : [];

        // Proměnné, které už v prostředí jsou, mají přednost před .env soubory — pro test je tedy
        // musíme odklidit, jinak by hodnoty z kontejneru přebily fixture.
        foreach ($this->testVariables() as $name) {
            putenv($name);
            unset($_ENV[$name], $_SERVER[$name]);
        }

        $this->workDir = sys_get_temp_dir().'/environment-test-'.uniqid();
        FileSystem::createDir($this->workDir);
    }

    protected function _after(): void
    {
        FileSystem::delete($this->workDir);

        foreach ($this->testVariables() as $name) {
            putenv($name);
            unset($_ENV[$name], $_SERVER[$name]);
        }

        foreach ($this->originalEnvironment as $name => $value) {
            putenv($name.'='.$value);
        }

        // Vrátí konfiguraci načtenou z reálného projektu, na které závisí ostatní testy.
        Environment::reload(dirname(__DIR__, 2));
    }

    public function testConfigurationUsesDefaultsWhenOnlyRequiredVariablesAreSet(): void
    {
        $this->writeEnv('.env', $this->requiredVariables());

        Environment::reload($this->workDir);
        $configuration = Environment::getConfiguration();

        self::assertSame('dev', $configuration['appEnv']);
        self::assertSame('http://moje-hospodareni.cz', $configuration['appBaseUrl']);
        self::assertSame('http://gotenberg:3000', $configuration['gotenbergUrl']);
        self::assertFalse($configuration['sendEmail'], 'v dev se maily neposílají');
        self::assertSame([], $configuration['errorEmails']);
        self::assertSame('dev', $configuration['environmentMode']);
        self::assertFalse($configuration['maintenance']['enabled']);
        self::assertSame([], $configuration['maintenance']['allowedIps']);
        self::assertTrue($configuration['tracyShowBar'], 'v dev je Tracy bar vidět');
        self::assertSame('db.example.com', $configuration['database']['host']);
        self::assertSame($this->workDir.'/app/config/google-credentials.json', $configuration['google']['credentialsPath']);
        self::assertSame('http://moje-hospodareni.cz/google/token', $configuration['google']['redirectUri']);
        self::assertNull($configuration['github']['token']);
        self::assertSame([], $configuration['github']['labels']);
        self::assertSame('12345', $configuration['skautis']['applicationId']);
        self::assertTrue($configuration['skautis']['testMode']);
        self::assertNull($configuration['sentry']['dsn']);
        self::assertSame('dev', $configuration['sentry']['releaseHash']);
    }

    public function testProductionEnvironmentFlipsEmailAndTracyDefaults(): void
    {
        $this->writeEnv('.env', $this->requiredVariables()."\nAPP_ENV=prod\n");

        Environment::reload($this->workDir);
        $configuration = Environment::getConfiguration();

        self::assertSame('prod', $configuration['appEnv']);
        self::assertTrue($configuration['sendEmail']);
        self::assertSame('prod', $configuration['environmentMode']);
        self::assertFalse($configuration['tracyShowBar']);
        self::assertFalse($configuration['skautis']['testMode']);
    }

    public function testEnvironmentSpecificFileOverridesBaseFile(): void
    {
        $this->writeEnv('.env', $this->requiredVariables()."\nAPP_ENV=staging\n");
        $this->writeEnv('.env.staging', "SEND_EMAIL=no\n");
        $this->writeEnv('.env.staging.local', "APP_BASE_URL=https://staging.example.com/\n");

        Environment::reload($this->workDir);
        $configuration = Environment::getConfiguration();

        self::assertSame('test', $configuration['environmentMode']);
        self::assertFalse($configuration['sendEmail']);
        self::assertSame('https://staging.example.com', $configuration['appBaseUrl'], 'koncové lomítko se odřezává');
        self::assertSame('https://staging.example.com/google/token', $configuration['google']['redirectUri']);
    }

    public function testCiEnvironmentIgnoresLocalOverrideAndUsesCiCredentials(): void
    {
        $this->writeEnv('.env', $this->requiredVariables()."\nAPP_ENV=ci\n");
        $this->writeEnv('.env.local', "APP_BASE_URL=https://local.example.com\n");

        Environment::reload($this->workDir);
        $configuration = Environment::getConfiguration();

        self::assertSame('test', $configuration['environmentMode']);
        self::assertSame('http://moje-hospodareni.cz', $configuration['appBaseUrl']);
        self::assertSame($this->workDir.'/app/config/ci-google-credentials.json', $configuration['google']['credentialsPath']);
    }

    public function testFileSyntaxSupportsCommentsQuotesExportPrefixAndInlineComments(): void
    {
        $this->writeEnv(
            '.env',
            $this->requiredVariables()
            ."# komentář\n"
            ."\n"
            ."export APP_BASE_URL=\"https://beta.example.com\"\n"
            ."MAINTENANCE_STARTED_AT_LABEL='pátek 3. 7.'\n"
            ."GOTENBERG_URL=http://gotenberg:3000 # interní služba\n"
            ."ERROR_EMAILS= admin@example.com , dev@example.com ,\n"
            ."BROKEN_LINE_WITHOUT_SEPARATOR\n"
            ."=chybí jméno\n",
        );

        Environment::reload($this->workDir);
        $configuration = Environment::getConfiguration();

        self::assertSame('https://beta.example.com', $configuration['appBaseUrl']);
        self::assertSame('pátek 3. 7.', $configuration['maintenance']['startedAtLabel']);
        self::assertSame('http://gotenberg:3000', $configuration['gotenbergUrl'], 'komentář za hodnotou se odřízne');
        self::assertSame(['admin@example.com', 'dev@example.com'], $configuration['errorEmails']);
    }

    public function testBooleanVariablesAcceptTextualValuesAndFallBackOnGarbage(): void
    {
        $this->writeEnv(
            '.env',
            $this->requiredVariables()
            ."SEND_EMAIL=yes\n"
            ."MAINTENANCE_MODE=ON\n"
            ."TRACY_SHOW_BAR=rozhodně\n"
            ."APP_MAINTENANCE_BYPASS=1\n"
            ."MAINTENANCE_ALLOWED_IPS=10.0.0.1,10.0.0.2\n",
        );

        Environment::reload($this->workDir);
        $configuration = Environment::getConfiguration();

        self::assertTrue($configuration['sendEmail']);
        self::assertTrue($configuration['maintenance']['enabled']);
        self::assertTrue($configuration['maintenance']['debugBypass']);
        self::assertTrue($configuration['tracyShowBar'], 'nesmyslná hodnota padá na default, bypass ho pak zapne');
        self::assertSame(['10.0.0.1', '10.0.0.2'], $configuration['maintenance']['allowedIps']);
    }

    public function testUnknownEnvironmentFallsBackToTestMode(): void
    {
        $this->writeEnv('.env', $this->requiredVariables()."APP_ENV=fialová\n");

        Environment::reload($this->workDir);

        self::assertSame('test', Environment::getConfiguration()['environmentMode']);
    }

    public function testAbsoluteGoogleCredentialsPathIsKeptAsIs(): void
    {
        $this->writeEnv('.env', $this->requiredVariables()."GOOGLE_CREDENTIALS_FILE=/etc/secrets/google.json\n");

        Environment::reload($this->workDir);

        self::assertSame('/etc/secrets/google.json', Environment::getConfiguration()['google']['credentialsPath']);
    }

    public function testMissingRequiredVariableFailsWithItsName(): void
    {
        $this->writeEnv('.env', "DB_HOST=db.example.com\nDB_USER=user\nDB_PASSWORD=secret\nDB_NAME=db\n");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing required environment variable APPLICATION_ID.');

        Environment::reload($this->workDir);
    }

    public function testRepeatedLoadOfTheSameProjectIsNoOp(): void
    {
        $this->writeEnv('.env', $this->requiredVariables()."APP_BASE_URL=https://first.example.com\n");
        Environment::reload($this->workDir);

        $this->writeEnv('.env', $this->requiredVariables()."APP_BASE_URL=https://second.example.com\n");
        Environment::load($this->workDir);

        self::assertSame('https://first.example.com', Environment::getConfiguration()['appBaseUrl']);
    }

    private function requiredVariables(): string
    {
        return "DB_HOST=db.example.com\nDB_USER=user\nDB_PASSWORD=secret\nDB_NAME=db\nAPPLICATION_ID=12345\n";
    }

    private function writeEnv(string $name, string $contents): void
    {
        FileSystem::write($this->workDir.'/'.$name, $contents);
    }

    /** @return string[] */
    private function testVariables(): array
    {
        return [
            'APP_ENV',
            'APP_BASE_URL',
            'APP_MAINTENANCE_BYPASS',
            'APP_RELEASE_HASH',
            'APPLICATION_ID',
            'DB_HOST',
            'DB_USER',
            'DB_PASSWORD',
            'DB_NAME',
            'ERROR_EMAILS',
            'GOOGLE_CREDENTIALS_FILE',
            'GOOGLE_REDIRECT_URI',
            'GITHUB_ISSUES_LABELS',
            'GITHUB_ISSUES_OWNER',
            'GITHUB_ISSUES_REPOSITORY',
            'GITHUB_ISSUES_TOKEN',
            'GOTENBERG_URL',
            'MAINTENANCE_ALLOWED_IPS',
            'MAINTENANCE_MODE',
            'MAINTENANCE_STARTED_AT_LABEL',
            'SEND_EMAIL',
            'SENTRY_DSN',
            'SKAUTIS_TEST_MODE',
            'TRACY_SHOW_BAR',
            'BROKEN_LINE_WITHOUT_SEPARATOR',
        ];
    }
}
