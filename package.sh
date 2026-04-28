#!/usr/bin/env bash

set -euo pipefail

if [ $# = 0 ]; then
  echo "Usage: package.sh <path_to_repo> [-o source.tar.gz] [-v VERSION]"
  exit
fi

if [ `uname` = "Darwin" ]; then
  export COPYFILE_DISABLE=1
fi

REPO="$1"
shift
DIR="${TMPDIR:-/tmp}/mytinytodo.build"
CURDIR=`pwd`
ARCHIVE="$CURDIR/mytinytodo-v@VERSION-@REV.tar.gz"
VER=""
REV=""

while getopts ":o:v:" opt; do
  case $opt in
    o) ARCHIVE="$OPTARG" ;;
    v) VER="$OPTARG" ;;
  esac
done

rm -rf "$DIR"

git clone --depth 1 "$REPO" "$DIR" > /dev/null 2>&1
if [ ! -d "$DIR" ]; then
  echo "Error while clone"
fi

echo "> Repository was cloned to temp dir: $DIR";

#get current version number if not specified
if [ -z "$VER" ]; then
  VER=`grep -Eo "const\s+VERSION\s*=.*[^;]" $DIR/src/includes/vars.php | sed -n "s/.*['\"]\([^'\"]*\)'.*/\1/p"`
fi
if [ -z "$VER" ]; then
  echo "Can not detect version"
  rm -rf "$DIR"
  exit
fi

# get revision
REV="$(git show --format=format:%H --summary | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')"
if [ -z "$REV" ]; then
  echo "Can not detect revision"
  rm -rf "$DIR"
  exit
fi
REV="${REV:0:8}"
ARCHIVE="${ARCHIVE/@VERSION/$VER}"
ARCHIVE="${ARCHIVE/@REV/$REV}"

echo "> Version is $VER";

cd "$DIR/src"

rm -f docker-config.php
rm -f includes/lang/en-rtl.json
rm -f includes/lang/_percent.php
rm -f mtt-cmd.php
rm -f mtt-emergency.php
rm -f content/theme/images/svg2base64.php
touch content/theme/custom.css

cd ..

if composer --version > /dev/null 2>&1; then
  echo "> Run Composer"
  composer="composer"
else
  echo "> Run Composer (containerized)"
  composer="./composer.sh"
fi
$composer install --no-dev --no-interaction --optimize-autoloader
if [ $? -ne 0 ]; then
  echo "Failed to install libs with composer"
  cd "$CURDIR" && rm -rf "$DIR"
  exit
fi

rm -rf src/includes/vendor/erusev/parsedown/.github

# clear extensions
mkdir src/ext2
cp src/ext/.htaccess src/ext2/
cp src/ext/index.html src/ext2/
rm -rf src/ext
mv src/ext2 src/ext


mv src mytinytodo
tar --no-xattrs -czf mytinytodo.tar.gz mytinytodo
if [ ! -f mytinytodo.tar.gz ]; then
  echo "Failed to pack files (no output tar.gz file)"
  cd "$CURDIR" && rm -rf "$DIR"
fi

cd "$CURDIR"
mv "$DIR/mytinytodo.tar.gz" "$ARCHIVE"

rm -rf "$DIR"
echo "> Temp dir has been cleaned"

echo "> Build is stored in $ARCHIVE"
