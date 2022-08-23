#!/bin/sh

#dir="$( dirname -- "$( readlink -f -- "$0"; )"; )"
dir="$PWD"

docker run -it --rm -v "$dir:/app" composer $@
