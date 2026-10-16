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


if [[ "$WP_VERSION" == "latest" ]]; then
    TESTS_TAG="trunk"
elif [[ "$WP_VERSION" =~ ^[0-9]+\.[0-9]+$ ]]; then
    TESTS_TAG="branches/$WP_VERSION"
else
    TESTS_TAG="tags/$WP_VERSION"
fi

if [[ "$TESTS_TAG" == "trunk" ]]; then
    TESTS_REPO="https://github.com/WordPress/wordpress-develop/archive/refs/heads/trunk.zip"
elif [[ "$TESTS_TAG" == branches/* ]]; then
    BRANCH="${TESTS_TAG#branches/}"
    TESTS_REPO="https://github.com/WordPress/wordpress-develop/archive/refs/heads/${BRANCH}.zip"
else
    TAG="${TESTS_TAG#tags/}"
    TESTS_REPO="https://github.com/WordPress/wordpress-develop/archive/refs/tags/${TAG}.zip"
fi

curl -fLsS "$TESTS_REPO" -o /tmp/wp-tests.zip

tar -xzf /tmp/wp-tests.zip --strip-components=1 -C /tmp

#tar -xzf /tmp/wordpress.tar.gz --strip-components=1 -C "$WP_CORE_DIR"


echo "### Download WP test suite (GitHub mirror)"


#TESTS_REPO="https://github.com/WordPress/wordpress-develop/archive/refs/${TESTS_TAG}.zip"

#download "$TESTS_REPO" /tmp/wp-tests.zip

TESTS_REPO="https://github.com/WordPress/wordpress-develop/archive/refs/${TESTS_TAG}.zip"
curl -fLsS "$TESTS_REPO" -o /tmp/wp-tests.zip

#if [ "$WP_VERSION" = "latest" ]; then
#    TESTS_REPO="https://github.com/WordPress/wordpress-develop/archive/refs/heads/trunk.zip"
#else
#    TESTS_REPO="https://github.com/WordPress/wordpress-develop/archive/refs/tags/${WP_VERSION}.zip"
#fi

#curl -fLsS "$TESTS_REPO" -o /tmp/wp-tests.zip

unzip -q /tmp/wp-tests.zip -d /tmp/

cp -R /tmp/wordpress-develop-trunk/tests/phpunit/includes "$WP_TESTS_DIR/"
cp -R /tmp/wordpress-develop-trunk/tests/phpunit/data "$WP_TESTS_DIR/"
cp /tmp/wordpress-develop-trunk/wp-tests-config-sample.php \
   "$WP_TESTS_DIR/wp-tests-config.php"

sed -i "s:dirname( __FILE__ ) . '/src/':'$WP_CORE_DIR/':" "$WP_TESTS_DIR/wp-tests-config.php"
sed -i "s/youremptytestdbnamehere/$DB_NAME/" "$WP_TESTS_DIR/wp-tests-config.php"
sed -i "s/yourusernamehere/$DB_USER/" "$WP_TESTS_DIR/wp-tests-config.php"
sed -i "s/yourpasswordhere/$DB_PASS/" "$WP_TESTS_DIR/wp-tests-config.php"
sed -i "s/localhost/$DB_HOST/" "$WP_TESTS_DIR/wp-tests-config.php"

echo "✓ WP tests installed (no svn)"
