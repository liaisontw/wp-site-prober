
#!/usr/bin/env bash
set -Eeuo pipefail

DB_NAME="${1:?Usage: $0 DB_NAME DB_USER DB_PASS DB_HOST [WP_VERSION]}"
DB_USER="${2:?Missing DB_USER}"
DB_PASS="${3:?Missing DB_PASS}"
DB_HOST="${4:?Missing DB_HOST}"
WP_VERSION="${5:-latest}"

WP_CORE_DIR="/tmp/wordpress"
WP_TESTS_DIR="/tmp/wordpress-tests-lib"
WP_CORE_ARCHIVE="/tmp/wordpress.tar.gz"
WP_TESTS_ARCHIVE="/tmp/wp-tests.tar.gz"

download() {
    curl -fLsS --retry 3 --retry-delay 2 "$1" -o "$2"
}

echo "### Resolve WordPress version"

if [[ "$WP_VERSION" == "latest" ]]; then
    WP_VERSION="$(
        curl -fLsS --retry 3 \
            https://api.wordpress.org/core/version-check/1.7/ |
        php -r '
            $data = json_decode(stream_get_contents(STDIN), true);
            if (!isset($data["offers"][0]["current"])) {
                fwrite(STDERR, "Unable to resolve latest WordPress version\n");
                exit(1);
            }
            echo $data["offers"][0]["current"];
        '
    )"
fi

echo "WP_VERSION=[$WP_VERSION]"

if [[ ! "$WP_VERSION" =~ ^[0-9]+\.[0-9]+(\.[0-9]+)?$ ]]; then
    echo "Error: Unsupported WP_VERSION format: $WP_VERSION" >&2
    exit 1
fi

if [[ "$WP_VERSION" =~ ^([0-9]+\.[0-9]+)$ ]]; then
    WP_BRANCH="${BASH_REMATCH[1]}"
    WP_TARBALL="https://wordpress.org/wordpress-${WP_VERSION}.tar.gz"
    TESTS_REF="branches/${WP_BRANCH}"
    TESTS_URL="https://github.com/WordPress/wordpress-develop/archive/refs/heads/${WP_BRANCH}.tar.gz"
else
    WP_TARBALL="https://wordpress.org/wordpress-${WP_VERSION}.tar.gz"
    TESTS_REF="tags/${WP_VERSION}"
    TESTS_URL="https://github.com/WordPress/wordpress-develop/archive/refs/tags/${WP_VERSION}.tar.gz"
fi

echo "WP_TARBALL=[$WP_TARBALL]"
echo "TESTS_REF=[$TESTS_REF]"

echo "### Download WordPress Core"

rm -rf "$WP_CORE_DIR" "$WP_TESTS_DIR"
mkdir -p "$WP_CORE_DIR" "$WP_TESTS_DIR"

download "$WP_TARBALL" "$WP_CORE_ARCHIVE"

if ! tar -tzf "$WP_CORE_ARCHIVE" >/dev/null 2>&1; then
    echo "Error: Invalid WordPress Core archive: $WP_TARBALL" >&2
    exit 1
fi

tar -xzf "$WP_CORE_ARCHIVE" \
    --strip-components=1 \
    -C "$WP_CORE_DIR"

echo "### Download WordPress test suite"
echo "TESTS_URL=[$TESTS_URL]"

download "$TESTS_URL" "$WP_TESTS_ARCHIVE"

if ! tar -tzf "$WP_TESTS_ARCHIVE" >/dev/null 2>&1; then
    echo "Error: Invalid WordPress test suite archive: $TESTS_URL" >&2
    exit 1
fi

tar -xzf "$WP_TESTS_ARCHIVE" -C /tmp/

if [[ "$TESTS_REF" == branches/* ]]; then
    TESTS_SOURCE="/tmp/wordpress-develop-${TESTS_REF#branches/}"
else
    TESTS_SOURCE="/tmp/wordpress-develop-${TESTS_REF#tags/}"
fi

if [[ ! -d "$TESTS_SOURCE/tests/phpunit/includes" ]]; then
    echo "Error: WordPress test suite not found in $TESTS_SOURCE" >&2
    exit 1
fi

cp -R "$TESTS_SOURCE/tests/phpunit/includes" "$WP_TESTS_DIR/"
cp -R "$TESTS_SOURCE/tests/phpunit/data" "$WP_TESTS_DIR/"

download \
    "https://raw.githubusercontent.com/WordPress/wordpress-develop/${TESTS_REF}/wp-tests-config-sample.php" \
    "$WP_TESTS_DIR/wp-tests-config.php"

echo "### Configure WordPress test suite"

php - "$WP_TESTS_DIR/wp-tests-config.php" "$DB_NAME" "$DB_USER" "$DB_PASS" "$DB_HOST" "$WP_CORE_DIR" <<'PHP'
<?php

$config_file = $argv[1];
$db_name     = $argv[2];
$db_user     = $argv[3];
$db_pass     = $argv[4];
$db_host     = $argv[5];
$wp_core_dir = $argv[6];

$config = file_get_contents($config_file);

if (false === $config) {
    fwrite(STDERR, "Unable to read wp-tests-config.php\n");
    exit(1);
}

$replacements = [
    'youremptytestdbnamehere' => $db_name,
    'yourusernamehere'       => $db_user,
    'yourpasswordhere'       => $db_pass,
    'localhost'              => $db_host,
];

$config = str_replace(
    array_keys($replacements),
    array_values($replacements),
    $config
);

$config = str_replace(
    "dirname( __FILE__ ) . '/src/'",
    var_export($wp_core_dir . '/', true),
    $config
);

if (false === file_put_contents($config_file, $config)) {
    fwrite(STDERR, "Unable to write wp-tests-config.php\n");
    exit(1);
}
PHP

echo "### Verify installed WordPress version"
grep -n '^\$wp_version' "$WP_CORE_DIR/wp-includes/version.php"

echo "WordPress Core: $WP_VERSION"
echo "Test suite: $TESTS_REF"
echo "✓ WordPress test suite installed"

