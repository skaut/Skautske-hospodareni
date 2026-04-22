<?php

namespace Deployer;

require_once 'contrib/rsync.php';
require_once 'recipe/common.php';

// === Release pojmenování ===
$short = substr(getenv('GITHUB_SHA') ?: (getenv('CI_COMMIT_SHA') ?: (getenv('COMMIT_SHA') ?: '')), 0, 8);
$stamp = date('Ymd-His');
$hash  = getenv('BUILD_HASH') ?: ($short ? "$stamp-$short" : $stamp);
$hash  = preg_replace('~[^A-Za-z0-9._-]~', '', $hash);

set('application', 'skautske-hospodareni');
set('keep_releases', 2);
set('build_hash', $hash);
set('release_name', fn () => get('build_hash'));
set('web_root_symlink', '{{deploy_path}}/www');

// === SSH ===
set('ssh_multiplexing', false);
//set('bin/ssh', 'ssh -o IdentitiesOnly=yes -o PreferredAuthentications=publickey -o PubkeyAuthentication=yes -o StrictHostKeyChecking=yes -o UserKnownHostsFile=/root/.ssh/known_hosts');

// === Shared / writable dirs ===
set('shared_dirs', []);
set('shared_files', ['.env.local', 'app/config/google-credentials.json']);

// === Rsync ===
set('rsync_src', __DIR__ . '/');
set('rsync_dest', '{{release_path}}');
set('rsync', static function () {
    return [
        'exclude' => [
            '.git',
            '.github',
            '.idea',
            '.docs',
            '/tests/',
            '/temp/',
            'node_modules/',
            'code-quality/',
            'workdir.tar.gz',
            '/log/',
            '/uploads/',
            '.env.local',
            '.env.*.local',
            'app/config/config.*.local.neon',
            'app/config/google-credentials.json',
        ],
        'exclude-file'  => false,
        'include'       => [],
        'include-file'  => false,
        'filter-file'   => false,
        'filter-perdir' => false,
        'flags'         => 'rz',
        'options'       => ['delete'],
        'timeout'       => 180,
    ];
});

function requiredEnv(string $name): string
{
    $value = getenv($name);
    if ($value === false ) {
        throw new \RuntimeException(sprintf('Missing required deploy variable "%s".', $name));
    }

    return $value;
}

function optionalEnv(string $name): ?string
{
    $value = getenv($name);

    if ($value === false || $value === '') {
        return null;
    }

    return $value;
}

function buildDotenvContent(array $values): string
{
    $lines = [];

    foreach ($values as $name => $value) {
        $escaped = str_replace(["\\", "\n", "\r", "\""], ["\\\\", "\\n", '', "\\\""], $value);
        $lines[] = sprintf('%s="%s"', $name, $escaped);
    }

    return implode(PHP_EOL, $lines).PHP_EOL;
}

task('debug:runtime_env', function () {
    writeln('<comment>=== Deploy runtime environment ===</comment>');

    $requiredVariables = [
        'APP_ENV',
        'APP_BASE_URL',
        'APPLICATION_ID',
        'SEND_EMAIL',
        'DB_HOST',
        'DB_NAME',
        'DB_USER',
        'GOOGLE_CREDENTIALS',
    ];

    $maskedRequiredVariables = [
        'DB_PASSWORD',
        'DB_FIX_TOKEN_SHA256',
    ];

    $optionalVariables = [
        'SKAUTIS_TEST_MODE',
        'TEST_BACKGROUND',
        'TRACY_SHOW_BAR',
        'GOOGLE_CREDENTIALS_FILE',
        'SENTRY_DSN',
        'APP_RELEASE_HASH',
    ];

    foreach ($requiredVariables as $name) {
        $value = getenv($name);
        writeln(sprintf('%s=%s', $name, $value === false ? '<missing>' : $value));
    }

    foreach ($maskedRequiredVariables as $name) {
        $value = getenv($name);
        writeln(sprintf('%s=%s', $name, $value === false || $value === '' ? '<missing>' : '<set>'));
    }

    foreach ($optionalVariables as $name) {
        $value = getenv($name);
        writeln(sprintf('%s=%s', $name, $value === false ? '<missing>' : $value));
    }
})->desc('Print deploy runtime environment');

task('build:runtime_files', function () {
    $runtimeDir = __DIR__.'/.deployment';
    $appEnv = requiredEnv('APP_ENV');

    if (! is_dir($runtimeDir) && ! mkdir($runtimeDir, 0777, true) && ! is_dir($runtimeDir)) {
        throw new \RuntimeException(sprintf('Unable to create runtime directory "%s".', $runtimeDir));
    }

    $values = [
        'APP_ENV' => $appEnv,
        'APP_BASE_URL' => requiredEnv('APP_BASE_URL'),
        'APPLICATION_ID' => requiredEnv('APPLICATION_ID'),
        'SEND_EMAIL' => requiredEnv('SEND_EMAIL'),
        'TRACY_SHOW_BAR' => optionalEnv('TRACY_SHOW_BAR') ?? ($appEnv === 'dev' ? 'true' : 'false'),
        'DB_HOST' => requiredEnv('DB_HOST'),
        'DB_NAME' => requiredEnv('DB_NAME'),
        'DB_USER' => requiredEnv('DB_USER'),
        'DB_PASSWORD' => requiredEnv('DB_PASSWORD'),
        'GOOGLE_CREDENTIALS_FILE' => optionalEnv('GOOGLE_CREDENTIALS_FILE') ?? 'google-credentials.json',
        'APP_RELEASE_HASH' => optionalEnv('APP_RELEASE_HASH') ?? get('build_hash'),
    ];

    foreach (['SKAUTIS_TEST_MODE', 'TEST_BACKGROUND', 'SENTRY_DSN'] as $name) {
        $value = optionalEnv($name);
        if ($value !== null) {
            $values[$name] = $value;
        }
    }

    $dbFixTokenSha256 = optionalEnv('DB_FIX_TOKEN_SHA256');
    if ($dbFixTokenSha256 !== null) {
        $values['DB_FIX_TOKEN_SHA256'] = $dbFixTokenSha256;
    }

    file_put_contents($runtimeDir.'/.env.local', buildDotenvContent($values));
    file_put_contents($runtimeDir.'/google-credentials.json', requiredEnv('GOOGLE_CREDENTIALS'));

    writeln('<info>Runtime files prepared.</info>');
})->desc('Prepare shared runtime files');

task('deploy:runtime_files', function () {
    $runtimeDir = __DIR__.'/.deployment';

    run('mkdir -p {{deploy_path}}/shared/app/config');
    upload($runtimeDir.'/.env.local', '{{deploy_path}}/shared/.env.local');
    upload($runtimeDir.'/google-credentials.json', '{{deploy_path}}/shared/app/config/google-credentials.json');
})->desc('Upload shared runtime files');

// --- úkol: vytvoř kořenové sdílené složky a symlinky v release ---
desc('Symlink root-level shared folders (log, uploads) into the release and update /www');
task('custom:shared_symlinks', function () {
    // zajisti existenci kořenových složek
    run('mkdir -p {{deploy_path}}/log {{deploy_path}}/uploads');

    // ukliď v release, ať ln nepadá, a vytvoř symlinky jako ve skriptu
    run('rm -rf {{release_path}}/log {{release_path}}/uploads');
    run('ln -s {{deploy_path}}/log {{release_path}}/log || true');
    run('ln -s {{deploy_path}}/uploads {{release_path}}/uploads || true');

    // symlink web rootu mimo releases (idempotentně, přepíše existující)
    run('ln -sfn {{release_path}}/www {{web_root_symlink}}');
});


// === Helper pro definici hostů z env ===
function addEnvHost(string $name, array $defaults): void {
    $prefix = strtoupper($name);

    $hostname   = getenv("HOST") ?: $defaults['host'];
    $user       = getenv("SSH_USERNAME") ?: $defaults['user'];
    $port       = (int) getenv("PORT") ?: $defaults['port'];
    $deployPath = getenv("ROOT_DIR") ?: $defaults['path'];

    host($name)
        ->setSshArguments(['-o UserKnownHostsFile=/dev/null', '-o StrictHostKeyChecking=no'])
        ->setHostname($hostname)
        ->set('remote_user', $user)
        ->setPort($port)
        ->set('deploy_path', $deployPath)
        ->set('bin/php', '/usr/bin/php8.3');
}

// === Definice prostředí ===
$defaults = [
    'host' => 'www.skauting.cz',
    'user' => 'vu011961',
    'port' => 11961,
    'path' => '/home/vu011961',
];

addEnvHost('beta', $defaults);
addEnvHost('test', $defaults);
addEnvHost('production', $defaults);

// === App příkazy ===
desc('Generate Doctrine proxies (remote)');
task('app:proxies', function () {
    run('cd {{release_path}} && {{bin/php}} bin/console orm:generate-proxies');
});

desc('Run DB migrations (remote)');
task('app:migrate', function () {
    run('cd {{release_path}} && {{bin/php}} bin/console migrations:migrate --no-interaction');
});

desc('Run DB fix endpoint via HTTP');
task('app:db_fix', function () {
    $appBaseUrl = rtrim(requiredEnv('APP_BASE_URL'), '/');
    $dbFixTokenSha256 = requiredEnv('DB_FIX_TOKEN_SHA256');
    $dbFixUrl = sprintf('%s/db_fix.php?token=%s', $appBaseUrl, rawurlencode($dbFixTokenSha256));

    runLocally(sprintf('curl --fail --silent --show-error %s', escapeshellarg($dbFixUrl)));
});

desc('Disable db fix script in current release');
task('app:disable_db_fix', function () {
    run('rm -f {{release_path}}/www/db_fix.php');
});

desc('Run DB migrations (remote)');
task('deploy:ssh_warmup', function () {
    $h = currentHost()->connectionString();
    runLocally("ssh -p 28 -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no $h echo");
});




// === Deploy pipeline ===
desc('Deploy via tar');
task('deploy', [
  //  'deploy:ssh_warmup',
    'deploy:info',
    'deploy:setup',
    'debug:runtime_env',
    'build:runtime_files',
    'deploy:lock',
    'deploy:release',
    'rsync',
    'deploy:runtime_files',
    'deploy:shared',
    'custom:shared_symlinks',
    'app:proxies',
    //'app:migrate', -- nelze na lebedě
    'deploy:publish',
    'app:db_fix', //lebeda...
    'app:disable_db_fix',
]);

after('deploy:failed', 'deploy:unlock');
