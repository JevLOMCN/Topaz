#!/bin/bash
# This file lists the top-level requires from MediaWiki core and extensions
# (as opposed to composer.json which pins both top-level requires and their
# dependencies).

# You can generate the blocks with 
#   cat /path/to/composer.json | jq --raw-output '.require|to_entries[]|select(.key|startswith("ext-")|not)|select(.key!="php")|@sh"require \(.key) \(.value)"'
# To test whether the requirements are valid (non-conflicting) just run this file. To see whether everything is pinned in composer.json run this file then run
#   diff <(git show @:composer.lock | jq --raw-output '.packages[].name') <(cat composer.lock | jq --raw-output '.packages[].name')
# To test whether there are orphaned requires in composer.json delete requires from it, run this file and run the diff above.

set -e

require() {
	composer require --update-no-dev --ignore-platform-reqs $1 $2
}

# mediawiki/core
require 'composer/semver' '1.5.0'
require 'cssjanus/cssjanus' '1.3.0'
require 'guzzlehttp/guzzle' '6.3.3'
require 'liuggio/statsd-php-client' '1.0.18'
require 'oojs/oojs-ui' '0.35.1'
require 'pear/mail' '1.4.1'
require 'pear/mail_mime' '1.10.4'
require 'pear/net_smtp' '1.8.1'
require 'psr/log' '1.0.2'
require 'wikimedia/assert' '0.2.2'
require 'wikimedia/at-ease' '1.2.0'
require 'wikimedia/base-convert' '2.0.0'
require 'wikimedia/cdb' '1.4.1'
require 'wikimedia/cldr-plural-rule-parser' '1.0.0'
require 'wikimedia/composer-merge-plugin' '1.4.1'
require 'wikimedia/html-formatter' '1.0.2'
require 'wikimedia/ip-set' '2.1.0'
require 'wikimedia/less.php' '1.8.0'
require 'wikimedia/object-factory' '2.1.0'
require 'wikimedia/password-blacklist' '0.1.4'
require 'wikimedia/php-session-serializer' '1.0.7'
require 'wikimedia/purtle' '1.0.7'
require 'wikimedia/relpath' '2.1.1'
require 'wikimedia/remex-html' '2.0.1'
require 'wikimedia/running-stat' '1.2.1'
require 'wikimedia/scoped-callback' '3.0.0'
require 'wikimedia/utfnormal' '2.0.0'
require 'wikimedia/timestamp' '3.0.0'
require 'wikimedia/wait-condition-loop' '1.0.1'
require 'wikimedia/wrappedstring' '3.0.1'
require 'wikimedia/xmp-reader' '0.6.3'
require 'zordius/lightncandy' '0.23'
# mediawiki/core, dev
require 'psy/psysh' '0.9.9'
