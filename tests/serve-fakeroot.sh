#!/bin/sh
set -e

# Start PHP built-in server with an isolated (fake) application root.
# The fake root contains symlinks to the application files, its own config.php
# and its own SQLite database, so the real src/ directory is never modified.
#
# Environment variables:
#   MTT_TEST_ROOT         Fake root directory. If empty, a temporary directory
#                         is created and removed on exit.
#   MTT_TEST_CONFIG_FILE  Config file copied to <root>/config.php. If empty,
#                         tests/config-sqlite.php is used.
#   MTT_TEST_SQLITE_FILE  SQLite database copied to <root>/db/todolist.db.
#                         If empty, a clean database is created with MTTDBSetup.
#   MTT_TEST_SENDMAIL     Optional path to a sendmail-compatible command. It is
#                         passed to PHP as -d sendmail_path=... and can be used
#                         to capture test emails instead of sending them.
#
# Note: a custom config with a non-empty MTT_DB_PREFIX requires passing a
# ready database via MTT_TEST_SQLITE_FILE, because a new database is created
# with an empty table prefix.
#
# Usage: ./serve-fakeroot.sh [port]
# Default port: 8080

if ! command -v php >/dev/null 2>&1
then
    echo "Error: PHP not found"
    exit 1
fi

PORT=${1:-8080}
HERE="$(cd "$(dirname "$0")" && pwd -P)"
SRC="$(cd "$HERE/../src" && pwd -P)"
DEFAULT_CONFIG="$HERE/config-sqlite.php"

if [ -n "${MTT_TEST_ROOT:-}" ]; then
    ROOT=$MTT_TEST_ROOT
    KEEP_ROOT=1
else
    ROOT=$(mktemp -d "${TMPDIR:-/tmp}/mtt-serve-XXXXXX")
    KEEP_ROOT=0
fi

mkdir -p "$ROOT" "$ROOT/db"
ROOT="$(cd "$ROOT" && pwd -P)"

DB_FILE="$ROOT/db/todolist.db"
CONFIG_FILE="$ROOT/config.php"
BOOTSTRAP_FILE="$ROOT/.serve-bootstrap.php"

cleanup() {
    echo ""
    echo "Stopping server..."
    if [ "$KEEP_ROOT" -eq 0 ]; then
        rm -rf "$ROOT"
        echo "Removed temporary root: $ROOT"
    else
        echo "Kept root: $ROOT"
    fi
}
trap cleanup EXIT
trap 'exit 130' INT TERM

if [ "$ROOT" = "$SRC" ]; then
    echo "Error: MTT_TEST_ROOT must not be the source directory"
    exit 1
fi

# Symlinks to the application. The first four are required by init.php/load.php
# and the API; the rest are needed to serve the web interface and static files.
for name in init.php load.php api.php includes index.php export.php feed.php content ext
do
    if [ -e "$ROOT/$name" ] || [ -L "$ROOT/$name" ]; then
        rm -rf "$ROOT/$name"
    fi
    ln -s "$SRC/$name" "$ROOT/$name"
done

# Config is always loaded from <root>/config.php (see load.php:requireConfig()).
if [ -n "${MTT_TEST_CONFIG_FILE:-}" ]; then
    if [ ! -f "$MTT_TEST_CONFIG_FILE" ]; then
        echo "Error: config file not found: $MTT_TEST_CONFIG_FILE"
        exit 1
    fi
    cp -f "$MTT_TEST_CONFIG_FILE" "$CONFIG_FILE"
    CONFIG_SOURCE=$MTT_TEST_CONFIG_FILE
else
    if [ ! -f "$DEFAULT_CONFIG" ]; then
        echo "Error: default test config not found: $DEFAULT_CONFIG"
        exit 1
    fi
    cp -f "$DEFAULT_CONFIG" "$CONFIG_FILE"
    CONFIG_SOURCE=$DEFAULT_CONFIG
fi

# SQLite database is always <root>/db/todolist.db (see init.php).
if [ -n "${MTT_TEST_SQLITE_FILE:-}" ]; then
    if [ ! -f "$MTT_TEST_SQLITE_FILE" ]; then
        echo "Error: sqlite file not found: $MTT_TEST_SQLITE_FILE"
        exit 1
    fi
    cp -f "$MTT_TEST_SQLITE_FILE" "$DB_FILE"
    DB_SOURCE=$MTT_TEST_SQLITE_FILE
else
    # Always start from a clean database when no ready file is provided.
    rm -f "$DB_FILE"
    MTT_SRC="$SRC" MTT_DB_FILE="$DB_FILE" php <<'PHP'
<?php
define('MTT_DEBUG', false);

$src = getenv('MTT_SRC');
$dbFile = getenv('MTT_DB_FILE');

require_once($src. '/includes/class.dbconnection.php');
require_once($src. '/includes/class.dbsetup.php');
require_once($src. '/includes/class.db.sqlite.php');
if (file_exists($src. '/includes/vendor/autoload.php')) {
    require_once($src. '/includes/vendor/autoload.php');
}

$db = new SqliteDatabase(null);
$db->connect(['filename' => $dbFile]);
$db->setPrefix('');
MTTDBSetup::createSqliteTables($db);

# Default user and list, same as in setup.php
$db->ex(
    "INSERT INTO users (id,username,name) VALUES (?,?,?)",
    [1, 'admin', 'admin']
);
$db->ex(
    "INSERT INTO lists (user_id,uuid,name,d_created,taskview) VALUES (?,?,?,?,?)",
    [1, bin2hex(random_bytes(16)), 'Todo', time(), 1]
);
PHP
    DB_SOURCE="created by MTTDBSetup"
fi

# PHP resolves symlinks in __FILE__/__DIR__, so init.php would use the real src/
# as MTTPATH. The prepended bootstrap defines MTTPATH before init.php runs and
# makes all application paths point to the fake root.
cat > "$BOOTSTRAP_FILE" <<'PHP'
<?php
if (!defined('MTTPATH')) {
    define('MTTPATH', __DIR__. '/');
}
PHP

echo "Starting PHP built-in server on http://localhost:${PORT}"
echo "Document root: ${ROOT}"
echo "Config:        ${CONFIG_SOURCE} -> ${CONFIG_FILE}"
echo "Database:      ${DB_SOURCE} -> ${DB_FILE}"
if [ -n "${MTT_TEST_SENDMAIL:-}" ]; then
    echo "Sendmail:      ${MTT_TEST_SENDMAIL}"
fi
echo "Press Ctrl+C to stop"
echo ""

PHP_ARGS="-d auto_prepend_file=$BOOTSTRAP_FILE"
if [ -n "${MTT_TEST_SENDMAIL:-}" ]; then
    PHP_ARGS="$PHP_ARGS -d sendmail_path=$MTT_TEST_SENDMAIL"
fi

cd "$ROOT"
exec php $PHP_ARGS -S "localhost:${PORT}" -t "$ROOT"
