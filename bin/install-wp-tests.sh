#!/usr/bin/env bash

set -e

DB_NAME=$1
DB_USER=$2
DB_PASS=$3
DB_HOST=$4
WP_VERSION=${5-latest}

WP_CORE_DIR=/tmp/wordpress
WP_TESTS_DIR=/tmp/wordpress-tests-lib

download() {
  local url=$1
  local dest=$2
  echo "Downloading: $url"
  curl -s -L "$url" -o "$dest"
}

echo "### (1) Downloading WordPress Core (${WP_VERSION})"
if [ ! -d "$WP_CORE_DIR" ]; then
    mkdir -p "$WP_CORE_DIR"

    if [ "$WP_VERSION" = "latest" ]; then
        WP_TARBALL="https://wordpress.org/latest.tar.gz"
    else
        WP_TARBALL="https://wordpress.org/wordpress-${WP_VERSION}.tar.gz"
    fi

    download "$WP_TARBALL" "/tmp/wordpress.tar.gz"
    tar -xzf /tmp/wordpress.tar.gz -C /tmp/
fi

echo "### (2) Downloading WP Test Suite (no svn needed!)"
if [ ! -d "$WP_TESTS_DIR" ]; then
    mkdir -p "$WP_TESTS_DIR"

    if [ "$WP_VERSION" = "latest" ]; then
        TESTS_TAG="trunk"
    else
        TESTS_TAG="tags/${WP_VERSION}"
    fi

    # Download includes/ and data/ from SVN, but as ZIP export — NO SVN client needed
    download "https://develop.svn.wordpress.org/${TESTS_TAG}/tests/phpunit/includes.zip" "/tmp/includes.zip"
    download "https://develop.svn.wordpress.org/${TESTS_TAG}/tests/phpunit/data.zip" "/tmp/data.zip"

    unzip -q "/tmp/includes.zip" -d "$WP_TESTS_DIR"
    unzip -q "/tmp/data.zip" -d "$WP_TESTS_DIR"

    # also need wp-tests-config-sample.php
    download "https://develop.svn.wordpress.org/${TESTS_TAG}/wp-tests-config-sample.php" \
             "$WP_TESTS_DIR/wp-tests-config.php"
fi

echo "### (3) Configuring wp-tests-config.php"

sed -i "s:dirname( __FILE__ ) . '/src/':'$WP_CORE_DIR/':" "$WP_TESTS_DIR/wp-tests-config.php"
sed -i "s/youremptytestdbnamehere/${DB_NAME}/" "$WP_TESTS_DIR/wp-tests-config.php"
sed -i "s/yourusernamehere/${DB_USER}/" "$WP_TESTS_DIR/wp-tests-config.php"
sed -i "s/yourpasswordhere/${DB_PASS}/" "$WP_TESTS_DIR/wp-tests-config.php"

# Replace DB host
ESCAPED_DB_HOST=$(printf '%s\n' "$DB_HOST" | sed 's/[&/\]/\\&/g')
sed -i "s/localhost/${ESCAPED_DB_HOST}/" "$WP_TESTS_DIR/wp-tests-config.php"


echo "### (4) Creating Test Database"

mysqladmin create "$DB_NAME" \
  --user="$DB_USER" \
  --password="$DB_PASS" \
  --host="$DB_HOST" \
  --protocol=tcp \
  2>/dev/null || true

echo "✓ WP Test Suite Installed Successfully (no SVN required)"
