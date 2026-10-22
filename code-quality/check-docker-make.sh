#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."

# Check host orchestration without contacting a daemon or running application tools.
export DOCKER_SOCKET="$PWD/.unused-docker.sock"
dry_run() {
    make --no-print-directory --dry-run CI_INPUTS_PREPARED=1 "$@" 2>&1
}
require_text() {
    if [[ "$1" != *"$2"* ]]; then
        printf 'Missing expected command: %s\n' "$2" >&2
        exit 1
    fi
}

for mode in 0 1; do
    user=docker
    if [[ "$mode" == 1 ]]; then user=root; fi
    development=$(dry_run DOCKER_ROOTLESS="$mode" enter enter-xdebug composer-install run CMD='npm run build')
    require_text "$development" "exec -u $user -it php bash"
    require_text "$development" "exec -u $user -it php-xdebug bash"
    require_text "$development" "--user $user php env COMPOSER_ROOT_VERSION="
    require_text "$development" "--user $user php npm run build"
    if [[ "$mode" == 1 ]]; then
        require_text "$development" '-f docker/docker-compose.rootless.yml'
    elif [[ "$development" == *docker-compose.rootless.yml* ]]; then
        echo 'Rootless override leaked into standard development.' >&2
        exit 1
    fi
done

standard=$(dry_run DOCKER_ROOTLESS=0 ci test-coverage ci-clean)
rootless=$(dry_run DOCKER_ROOTLESS=1 ci test-coverage ci-clean)
if [[ "$standard" != "$rootless" || "$rootless" == *docker-compose.rootless.yml* || "$rootless" == *'--user root'* ]]; then
    echo 'The rootless flag changed CI commands.' >&2
    exit 1
fi
require_text "$rootless" '--user docker php-test'

if invalid=$(dry_run DOCKER_ROOTLESS=yes enter); then
    echo 'Invalid rootless mode was accepted.' >&2
    exit 1
fi
require_text "$invalid" 'DOCKER_ROOTLESS must be 0 or 1'
if missing=$(dry_run DOCKER_ROOTLESS=0 run CMD=); then
    echo 'make run accepted an empty command.' >&2
    exit 1
fi
require_text "$missing" 'Specify a command'
echo 'Docker Make configuration checks passed.'
