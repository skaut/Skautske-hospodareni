#!/usr/bin/env bash

if [ -z "$1" ]; then
  echo "SSH file contents was not passed"
  exit 1
fi

if [ -z "$2" ]; then
  echo "SSH file path was not passed"
  exit 1
fi

mkdir -p /root/.ssh
echo "$1" > "$2"
mv .github/workflows/known_hosts /root/.ssh/known_hosts
chmod 700 /root/.ssh
chmod 644 /root/.ssh/known_hosts
chmod 400 "$2"
