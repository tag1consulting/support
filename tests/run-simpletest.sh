#!/bin/bash
# @file
# Simple script to run the tests.

set -e

# Goto current directory.
DIR=$(dirname $0)
cd $DIR

drush -y en simpletest support

export CORE_DIR=$(drush drupal-directory)

cd $CORE_DIR
{ php ./core/scripts/run-tests.sh --color --verbose --url 'http://127.0.0.1/' "support" || echo "1 fails"; } | tee /tmp/simpletest-result.txt

egrep -i "([1-9]+ fail)|(Fatal error)|([1-9]+ exception)" /tmp/simpletest-result.txt && exit 1
exit 0
