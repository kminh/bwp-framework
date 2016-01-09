#!/usr/bin/env bash

if [ $# -lt 3 ]; then
	echo "usage: $0 <db-name> <db-user> <db-pass> [db-host] [wp-version]"
	exit 1
fi

DB_NAME=$1
DB_USER=$2
DB_PASS=$3
DB_HOST=${4-localhost}
WP_VERSION=${5-latest}

WP_TESTS_DIR=${WP_TESTS_DIR-/tmp/wordpress-tests-lib}
WP_TESTS_DOMAIN=${WP_TESTS_DOMAIN-127.0.0.1}
WP_CORE_DIR=${WP_CORE_DIR-/tmp/wordpress/}

download() {
	if [ `which curl` ]; then
		curl -s "$1" > "$2";
	elif [ `which wget` ]; then
		wget -nv -O "$2" "$1"
	fi
}

# special versions that can only use test libs from trunk
if [[ $WP_VERSION == 'nightly' || $WP_VERSION == 'trunk' || $WP_VERSION =~ 'RC' \
   || $WP_VERSION =~ 'rc' || $WP_VERSION =~ 'beta' ]]; then
	WP_TESTS_TAG="trunk"
# latest is currently 4.4, but its test libs are buggy, so use 4.3 for now
elif [[ $WP_VERSION == 'latest' ]]; then
	WP_TESTS_TAG="tags/4.3"
elif [[ $WP_VERSION =~ [0-9]+\.[0-9]+(\.[0-9]+)? ]]; then
	# WP_TESTS_TAG="tags/$WP_VERSION"
	WP_TESTS_TAG="tags/4.3"
else
	# http serves a single offer, whereas https serves multiple. we only want one
	download http://api.wordpress.org/core/version-check/1.7/ /tmp/wp-latest.json
	grep '[0-9]+\.[0-9]+(\.[0-9]+)?' /tmp/wp-latest.json
	LATEST_VERSION=$(grep -o '"version":"[^"]*' /tmp/wp-latest.json | sed 's/"version":"//')
	if [[ -z "$LATEST_VERSION" ]]; then
		echo "Latest WordPress version could not be found"
		exit 1
	fi
	WP_TESTS_TAG="tags/$LATEST_VERSION"
fi

# set -ex
set -e

install_wp() {

	if [ -d $WP_CORE_DIR ]; then
		return;
	fi

	mkdir -p $WP_CORE_DIR

	if [[ $WP_VERSION == 'nightly' || $WP_VERSION == 'trunk' ]]; then
		mkdir -p /tmp/wordpress-nightly
		download https://wordpress.org/nightly-builds/wordpress-latest.zip  /tmp/wordpress-nightly/wordpress-nightly.zip
		unzip -q /tmp/wordpress-nightly/wordpress-nightly.zip -d /tmp/wordpress-nightly/
		mv /tmp/wordpress-nightly/wordpress/* $WP_CORE_DIR
	else
		if [ $WP_VERSION == 'latest' ]; then
			local ARCHIVE_NAME='latest'
		else
			local ARCHIVE_NAME="wordpress-$WP_VERSION"
		fi
		download https://wordpress.org/${ARCHIVE_NAME}.tar.gz  /tmp/wordpress.tar.gz
		tar --strip-components=1 -zxmf /tmp/wordpress.tar.gz -C $WP_CORE_DIR
	fi

	download https://raw.github.com/markoheijnen/wp-mysqli/master/db.php $WP_CORE_DIR/wp-content/db.php
}

install_test_suite() {
	# portable in-place argument for both GNU sed and Mac OSX sed
	if [[ $(uname -s) == 'Darwin' ]]; then
		local ioption='-i .bak'
	else
		local ioption='-i'
	fi

	# set up testing suite if it doesn't yet exist
	if [ ! -d $WP_TESTS_DIR ]; then
		# set up testing suite
		mkdir -p $WP_TESTS_DIR
		svn co --quiet https://develop.svn.wordpress.org/${WP_TESTS_TAG}/tests/phpunit/includes/ $WP_TESTS_DIR/includes
	fi

	cd $WP_TESTS_DIR

	if [ ! -f wp-tests-config.php ]; then
		download https://develop.svn.wordpress.org/${WP_TESTS_TAG}/wp-tests-config-sample.php "$WP_TESTS_DIR"/wp-tests-config.php
		sed $ioption "s:dirname( __FILE__ ) . '/src/':'$WP_CORE_DIR':" "$WP_TESTS_DIR"/wp-tests-config.php
		sed $ioption "s/youremptytestdbnamehere/$DB_NAME/" "$WP_TESTS_DIR"/wp-tests-config.php
		sed $ioption "s/yourusernamehere/$DB_USER/" "$WP_TESTS_DIR"/wp-tests-config.php
		sed $ioption "s/yourpasswordhere/$DB_PASS/" "$WP_TESTS_DIR"/wp-tests-config.php
		sed $ioption "s|localhost|${DB_HOST}|" "$WP_TESTS_DIR"/wp-tests-config.php

		# allow unlimited resources for testing
		sed $ioption "s|'php'|'php -d memory_limit=-1'|" "$WP_TESTS_DIR"/wp-tests-config.php

		# allow using different domains for testing
		sed $ioption "s|example.org|$WP_TESTS_DOMAIN|" "$WP_TESTS_DIR"/wp-tests-config.php
	fi

	# copy the config file to core dir so we can browse it in a browser, this
	# config file must be altered later on by test bootstrap
	cp -f $WP_TESTS_DIR/wp-tests-config.php $WP_CORE_DIR/wp-config-original.php

	cd $WP_TESTS_DIR/includes

	# hack to allow loading bootstrap file without installing WP again
	sed $ioption "s:^system( WP_PHP_BINARY.*install.php:if (!file_exists(dirname(dirname(__FILE__)) . '/installed.lock')) \0:" bootstrap.php
	sed $ioption "s:^_delete_all_posts():if (!file_exists(dirname(dirname(__FILE__)) . '/installed.lock')) \0:" bootstrap.php

	# hack to suppress useless WP messages
	sed $ioption "s:^\s*echo \"Running as multisite://\0:" bootstrap.php
	sed $ioption "s:^\s*echo \"Running as single site://\0:" bootstrap.php
	sed $ioption "s:^\s*echo sprintf( 'Not running://\0:" bootstrap.php

	# hack to allow using wp testcase class before we bootstrap WP
	sed $ioption "s:require dirname( __FILE__ ) . '/testcase.php':require_once dirname( __FILE__ ) . '/testcase.php';:" bootstrap.php
	sed $ioption "s/^class WP_UnitTestCase/abstract class WP_UnitTestCase/" testcase.php
}

install_db() {
	# parse DB_HOST for port or socket references
	local PARTS=(${DB_HOST//\:/ })
	local DB_HOSTNAME=${PARTS[0]};
	local DB_SOCK_OR_PORT=${PARTS[1]};
	local EXTRA=""

	if ! [ -z $DB_HOSTNAME ] ; then
		if [ $(echo $DB_SOCK_OR_PORT | grep -e '^[0-9]\{1,\}$') ]; then
			EXTRA=" --host=$DB_HOSTNAME --port=$DB_SOCK_OR_PORT --protocol=tcp"
		elif ! [ -z $DB_SOCK_OR_PORT ] ; then
			EXTRA=" --socket=$DB_SOCK_OR_PORT"
		elif ! [ -z $DB_HOSTNAME ] ; then
			EXTRA=" --host=$DB_HOSTNAME --protocol=tcp"
		fi
	fi

	# create database
	echo "DROP DATABASE IF EXISTS \`$DB_NAME\`" | mysql --user="$DB_USER" --password="$DB_PASS"$EXTRA
	mysqladmin create $DB_NAME --user="$DB_USER" --password="$DB_PASS"$EXTRA
}

install_wp
install_test_suite
install_db
