<?php

use Drupal\support_ticket\SupportTicketInterface;
use Drupal\Core\Access\AccessResult;

/**
 * @file
 * Hooks specific to the Support ticket module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Controls access to a support ticket.
 *
 * Modules may implement this hook if they want to have a say in whether or not
 * a given user has access to perform a given operation on a support ticket.
 *
 * Note that not all modules will want to influence access on all support ticket
 * types. If your module does not want to explicitly allow or forbid access,
 * return an AccessResultInterface object with neither isAllowed() nor
 * isForbidden() equaling TRUE. Blindly returning an object with isForbidden()
 * equaling TRUE will break other support ticket access modules.
 *
 * @param \Drupal\support_ticket\SupportTicketInterface|string $support_ticket
 *   Either a support ticket entity or the machine name of the content type on
 *   which to perform the access check.
 * @param string $op
 *   The operation to be performed. Possible values:
 *   - "create"
 *   - "delete"
 *   - "update"
 *   - "view"
 * @param \Drupal\Core\Session\AccountInterface $account
 *   The user object to perform the access check operation on.
 *
 * @return \Drupal\Core\Access\AccessResultInterface
 *    The access result.
 *
 * @ingroup support_ticket_access
 */
function hook_support_ticket_access(\Drupal\support_ticket\SupportTicketInterface $support_ticket, $op, \Drupal\Core\Session\AccountInterface $account) {
  $type = $support_ticket->bundle();

  switch ($op) {
    case 'create':
      return AccessResult::allowedIfHasPermission($account, 'create ' . $type . ' ticket');

    case 'update':
      if ($account->hasPermission('edit any ' . $type . ' ticket')) {
        return AccessResult::allowed()->cachePerPermissions();
      }
      else {
        return AccessResult::allowedIf($account->hasPermission('edit own ' . $type . ' ticket') && ($account->id() == $support_ticket->getOwnerId()))->cachePerPermissions()->cachePerUser()->cacheUntilEntityChanges($support_ticket);
      }

    case 'delete':
      if ($account->hasPermission('delete any ' . $type . ' ticket')) {
        return AccessResult::allowed()->cachePerPermissions();
      }
      else {
        return AccessResult::allowedIf($account->hasPermission('delete own ' . $type . ' ticket') && ($account->id() == $support_ticket->getOwnerId()))->cachePerPermissions()->cachePerUser()->cacheUntilEntityChanges($support_ticket);
      }

    default:
      // No opinion.
      return AccessResult::neutral();
  }
}

/**
 * Alter the links of a support ticket.
 *
 * @param array &$links
 *   A renderable array representing the support ticket links.
 * @param \Drupal\support_ticket\SupportTicketInterface $entity
 *   The support ticket being rendered.
 * @param array &$context
 *   Various aspects of the context in which the support ticket links are going to be
 *   displayed, with the following keys:
 *   - 'view_mode': the view mode in which the support ticket is being viewed
 *   - 'langcode': the language in which the support ticket is being viewed
 *
 * @see \Drupal\support_ticket\SupportTicketViewBuilder::renderLinks()
 * @see \Drupal\support_ticket\SupportTicketViewBuilder::buildLinks()
 * @see entity_crud
 */
function hook_support_ticket_links_alter(array &$links, SupportTicketInterface $entity, array &$context) {
  $links['mymodule'] = array(
    '#theme' => 'links__support_ticket__mymodule',
    '#attributes' => array('class' => array('links', 'inline')),
    '#links' => array(
      'support_ticket-report' => array(
        'title' => t('Report'),
        'href' => "support_ticket/{$entity->id()}/report",
        'query' => array('token' => \Drupal::getContainer()->get('csrf_token')->get("support_ticket/{$entity->id()}/report")),
      ),
    ),
  );
}

/**
 * @} End of "addtogroup hooks".
 */

