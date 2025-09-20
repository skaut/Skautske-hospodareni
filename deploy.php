<?php

namespace Deployer;

use Deployer\Task\Context;

require_once 'contrib/rsync.php';
require_once 'recipe/common.php';

// === Release pojmenování ===
$short = substr(getenv('GITHUB_SHA') ?: (getenv('CI_COMMIT_SHA') ?: (getenv('COMMIT_SHA') ?: '')), 0, 8);
$stamp = date('Ymd-His');
$hash  = getenv('BUILD_HASH') ?: ($short ? "$stamp-$short" : $stamp);
$hash  = preg_replace('~[^A-Za-z0-9._-]~', '', $hash);

set('application', 'skautske-hospodareni');
set('keep_releases', 3);
set('build_hash', $hash);
set('release_name', fn () => get('build_hash'));
set('web_root_symlink', '{{deploy_path}}/www');
set('keep_releases', 3);

// === SSH ===
set('ssh_multiplexing', false);
set('bin/ssh', 'ssh -o IdentitiesOnly=yes -o PreferredAuthentications=publickey -o PubkeyAuthentication=yes -o StrictHostKeyChecking=yes -o UserKnownHostsFile=/root/.ssh/known_hosts');

// === Shared / writable dirs ===
set('shared_dirs', []);

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

// spusť po dokončení standardního deploye (jakmile existuje {{release_path}})
after('deploy:symlink', 'custom:shared_symlinks');


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
addEnvHost('prod', $defaults);

// === App příkazy ===
desc('Generate Doctrine proxies (remote)');
task('app:proxies', function () {
    run('cd {{release_path}} && {{bin/php}} bin/console orm:generate-proxies');
});

desc('Run DB migrations (remote)');
task('app:migrate', function () {
    run('cd {{release_path}} && {{bin/php}} bin/console migrations:migrate --no-interaction');
});


set('src_path', __DIR__); // lokální zdroj (kontejner s Deployerem)

// Kořenové složky k vynechání (jen v rootu)
set('tar_excludes', [
    // původní root věci
    './.git',
    './.git/**',
    './.github',
    './.github/**',
    './tests',
    './tests/**',
    './temp',
    './temp/**',
    './node_modules',
    './node_modules/**',
    './releases',
    './releases/**',
    './log',
    './log/**',
    './uploads',
    './uploads/**',
    './workdir.tar.gz',
    'app/config/*local.neon',
]);

desc('Upload via tar|ssh (bez rsync na cíli)');
task('upload:tar', function () {
    $src  = rtrim(get('src_path'), '/');
    $dst  = parse('{{release_path}}');
    $host = Context::get()->getHost();

    run('mkdir -p ' . escapeshellarg($dst));

    $excludeArgs = implode(' ', array_map(
        fn($p) => '--exclude=' . escapeshellarg($p),
        get('tar_excludes')
    ));

    $cmd = sprintf(
        "tar -C %s -czf - %s . | ssh %s %s 'tar -C %s -xzf -'",
        escapeshellarg($src),
        $excludeArgs,
        $host->connectionOptionsString(),
        $host->connectionString(),
        escapeshellarg($dst)
    );

    runLocally($cmd);
});


task('upload:config', function () {
    $src  = rtrim(get('src_path'), '/');
    $dst  = parse('{{release_path}}');
    $host = Context::get()->getHost();

    $localFile  = $src . '/app/config/config.local.neon';
    $remoteFile = $dst . '/app/config/config.local.neon';

    if (is_file($localFile)) {
        $cmd = sprintf(
            "ssh %s %s 'mkdir -p %s && cat > %s'",
            $host->connectionOptionsString(),
            $host->connectionString(),
            escapeshellarg(dirname($remoteFile)),
            escapeshellarg($remoteFile)
        );

        runLocally("cat " . escapeshellarg($localFile) . " | " . $cmd);
    }
});


task('upload:info', function () {


    $host = Context::get()->getHost();
    var_dump($host->connectionOptionsString());
    $cmd = "echo OK";
    runLocally($cmd);
});


// === Deploy pipeline ===
desc('Deploy via tar');
task('deploy', [
    'deploy:info',
    'deploy:setup',
    'build:config',
    'deploy:lock',
    'deploy:release',
    'upload:tar',
    'upload:config',
    'custom:shared_symlinks',
    'deploy:writable',
    'app:proxies',
//    'app:migrate',
    'deploy:publish',
    'deploy:unlock',
    'deploy:cleanup',
]);

after('deploy:failed', 'deploy:unlock');
