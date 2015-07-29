#!/bin/bash
# @file
# Simple script to run the unit tests.

set -e

export CORE_DIR=$(drush drupal-directory)
export MODULE_DIR=$(cd $(dirname $0); cd ..; pwd)

(cd $CORE_DIR/core; ./vendor/bin/phpunit $MODULE_DIR/tests/src )
