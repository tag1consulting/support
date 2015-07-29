#!/bin/bash
# @file
# Simple script to load composer and run behat tests.

set -e

DIR=$(dirname $0)
cd $DIR
cd ./behat
test -f "./vendor/bin/behat" || composer install --no-interaction --prefer-source

# Checking for Phantom JS or selenium (Linux only)
if ! { netstat -l -n | grep -q :4444; }
then
	echo "Error: PhantomJS or selenium could not be found." 1>&2
	echo "" 1>&2
	echo "Start phantomjs with:" 1>&2
	echo "  phantomjs --webdriver=4444 &" 1>&2
	exit 1
fi

./vendor/bin/behat "$@"
