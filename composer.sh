#!/bin/sh

dir="$( dirname -- "$( readlink -f -- "$0"; )"; )"

docker run -it --rm -v "$dir:/app" composer $@
