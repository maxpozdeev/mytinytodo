#!/bin/sh

#dir="$( dirname -- "$( readlink -f -- "$0"; )"; )"
dir="$PWD"

app=$(which podman)
if [ -z $app ]; then
  app="docker"
fi

$app run -it --rm -v "$dir:/app" composer $@
