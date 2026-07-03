#!/usr/bin/env sh

set -eu

bin/console migrations:migrate --no-interaction

output_file="$(mktemp)"
trap 'rm -f "$output_file"' EXIT

if bin/console migrations:diff >"$output_file" 2>&1; then
    cat "$output_file"
    echo "Doctrine mapping differs from migrations. A new migration was generated."
    exit 1
fi

if grep -q 'No changes detected in your mapping information' "$output_file"; then
    echo "Doctrine mapping is in sync with migrations."
    exit 0
fi

cat "$output_file"
exit 1
