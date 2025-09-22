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

// === Rsync ===
set('rsync_src', __DIR__ . '/');
set('rsync_dest', '{{release_path}}');
set('rsync', static function () {
    $stage = currentHost()->get('stage');

    return [
        // běžné exclude vzory – BEZ "./"
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
        ],

        // řešení priority: nejdřív explicitně povol konkrétní soubor, pak zakaž wildcard
        'filter' => [
            '+ app/config/config.' . $stage . '.local.neon',
            '- app/config/config.*.local.neon',
        ],

        // ostatní volby
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



task('build:config', function () {
    $env = getenv('ENVIRONMENT') ?: 'local';
    $src = sprintf('app/config/config.%s.local.neon', $env);
    $dest = 'app/config/config.local.neon';

    if (!file_exists($src)) {
        throw new \RuntimeException("Missing template: $src");
    }

    $content = file_get_contents($src);

    // nahrazení tokenů __TOKEN__
    $replacements = [
        '__CONFIG_DATABASE_PASSWORD__' => getenv('CONFIG_DATABASE_PASSWORD') ?: '',
        '__CONFIG_SENTRY_DSN__'        => getenv('CONFIG_SENTRY_DSN') ?: '',
        '__CONFIG_APPLICATION_ID__'    => getenv('CONFIG_APPLICATION_ID') ?: '',
    ];
    $content = strtr($content, $replacements);

    file_put_contents($dest, $content);

    writeln("<info>Config generated → $dest</info>");
})->desc('Generate local config.local.neon');


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
    'build:config',
    'deploy:lock',
    'deploy:release',
    'rsync',
    'custom:shared_symlinks',
    'app:proxies',
    //'app:migrate',
    'deploy:publish'
]);

after('deploy:failed', 'deploy:unlock');
