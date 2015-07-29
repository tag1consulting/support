#!/bin/bash
# @file
# Simple script to run all tests.

set -e

./tests/run-phpunit.sh
./tests/run-simpletest.sh
./tests/run-behat.sh
