#!/usr/bin/env bash
set -e

DB_NAME=$1
DB_USER=$2
DB_PASS=$3
DB_HOST=$4
WP_VERSION=${5:-latest}

WP_CORE_DIR=/tmp/wordpress
WP_TESTS_DIR=/tmp/wordpress-tests-lib

download() {
  curl -sL "$1" -o "$2"
}

echo "### Download WordPress Core"

rm -rf "$WP_CORE_DIR" "$WP_TESTS_DIR"
mkdir -p "$WP_CORE_DIR" "$WP_TESTS_DIR"

if [ "$WP_VERSION" = "latest" ]; then
  WP_TARBALL="https://wordpress.org/latest.tar.gz"
  TESTS_REPO="https://github.com/WordPress/wordpress-develop/archive/refs/heads/trunk.zip"
else
  WP_TARBALL="https://wordpress.org/wordpress-${WP_VERSION}.tar.gz"
  TESTS_REPO="https://github.com/WordPress/wordpress-develop/archive/refs/tags/${WP_VERSION}.zip"
fi

download "$WP_TARBALL" /tmp/wordpress.tar.gz
tar -xzf /tmp/wordpress.tar.gz --strip-components=1 -C "$WP_CORE_DIR"

echo "### Download WP test suite (GitHub mirror)"
curl -fLsS "$TESTS_REPO" -o /tmp/wp-tests.zip

# 清理舊的解壓目錄（以防萬一）
rm -rf /tmp/wordpress-develop-*

unzip -q /tmp/wp-tests.zip -d /tmp/

# 動態尋找解壓縮出來的資料夾（不管是 trunk 還是特定 tag 版本）
WP_DEV_DIR=$(ls -d /tmp/wordpress-develop-* | head -n 1)

cp -R "$WP_DEV_DIR/tests/phpunit/includes" "$WP_TESTS_DIR/"
cp -R "$WP_DEV_DIR/tests/phpunit/data" "$WP_TESTS_DIR/"
cp "$WP_DEV_DIR/wp-tests-config-sample.php" "$WP_TESTS_DIR/wp-tests-config.php"

sed -i "s:dirname( __FILE__ ) . '/src/':'$WP_CORE_DIR/':" "$WP_TESTS_DIR/wp-tests-config.php"
sed -i "s/youremptytestdbnamehere/$DB_NAME/" "$WP_TESTS_DIR/wp-tests-config.php"
sed -i "s/yourusernamehere/$DB_USER/" "$WP_TESTS_DIR/wp-tests-config.php"
sed -i "s/yourpasswordhere/$DB_PASS/" "$WP_TESTS_DIR/wp-tests-config.php"
sed -i "s/localhost/$DB_HOST/" "$WP_TESTS_DIR/wp-tests-config.php"

echo "✓ WP tests installed (no svn)"