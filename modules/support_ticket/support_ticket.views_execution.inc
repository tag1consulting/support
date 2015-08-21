<?php

/**
 * @file
 * Provide views runtime hooks for support_ticket.module.
 */

use Drupal\user\RoleInterface;
use Drupal\views\ViewExecutable;
use Drupal\user\Entity\Role;

/**
 * Implements hook_views_query_substitutions().
 */
function support_ticket_views_query_substitutions(ViewExecutable $view) {
  $account = \Drupal::currentUser();
  return array(
    '***ADMINISTER_SUPPORT_TICKETS***' => intval($account->hasPermission('administer support tickets')),
    '***VIEW_OWN_UNPUBLISHED_SUPPORT_TICKETS***' => intval($account->hasPermission('view own unpublished tickets')),
  );
}

/**
 * Implements hook_views_analyze().
 */
function support_ticket_views_analyze(ViewExecutable $view) {
  $ret = array();
  // Check for something other than the default display:
  if ($view->storage->get('base_table') == 'support_ticket') {
    foreach ($view->displayHandlers as $display) {
      if (!$display->isDefaulted('access') || !$display->isDefaulted('filters')) {
        // check for no access control
        $access = $display->getOption('access');
        if (empty($access['type']) || $access['type'] == 'none') {
          $anonymous_role = Role::load(RoleInterface::ANONYMOUS_ID);
          $anonymous_has_access = $anonymous_role && $anonymous_role->hasPermission('access support tickets');
          $authenticated_role = Role::load(RoleInterface::AUTHENTICATED_ID);
          $authenticated_has_access = $authenticated_role && $authenticated_role->hasPermission('access support tickets');
          if (!$anonymous_has_access || !$authenticated_has_access) {
            $ret[] = Analyzer::formatMessage(t('Some roles lack permission to access support tickets, but display %display has no access control.', array('%display' => $display->display['display_title'])), 'warning');
          }
          $filters = $display->getOption('filters');
          foreach ($filters as $filter) {
            if ($filter['table'] == 'support_ticket' && ($filter['field'] == 'status' || $filter['field'] == 'status_extra')) {
              continue 2;
            }
          }
          $ret[] = Analyzer::formatMessage(t('Display %display has no access control but does not contain a filter for published support tickets.', array('%display' => $display->display['display_title'])), 'warning');
        }
      }
    }
  }
  foreach ($view->displayHandlers as $display) {
    if ($display->getPluginId() == 'page') {
      if ($display->getOption('path') == 'support_ticket/%') {
        $ret[] = Analyzer::formatMessage(t('Display %display has set support_ticket/% as path. This will not produce what you want. If you want to have multiple versions of the support ticket view, use panels.', array('%display' => $display->display['display_title'])), 'warning');
      }
    }
  }

  return $ret;
}
