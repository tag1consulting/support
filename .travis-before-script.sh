#!/bin/bash

set -e $DRUPAL_TI_DEBUG

# Increase the MySQL server timeout and packet size.
mysql -e "SET GLOBAL wait_timeout = 36000;"
mysql -e "SET GLOBAL max_allowed_packet = 33554432;"

# Ensure the right Drupal version is installed.
# Note: This function is re-entrant.
drupal_ti_ensure_drupal

# Add custom modules to drupal build.
cd "$DRUPAL_TI_DRUPAL_DIR"

# Ensure the module is linked into the codebase.
drupal_ti_ensure_module

# Enable main module and submodules.
drush en -y support support_ticket
