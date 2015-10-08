<?php

/**
 * @file
 * Contains \Drupal\support_ticket\Access\SupportTicketRevisionAccessCheck.
 */

namespace Drupal\support_ticket\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\support_ticket\SupportTicketInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides an access checker for support_ticket revisions.
 *
 * @ingroup support_ticket_access
 */
class SupportTicketRevisionAccessCheck implements AccessInterface {

  /**
   * The support_ticket storage.
   *
   * @var \Drupal\support_ticket\SupportTicketStorageInterface
   */
  protected $supportTicketStorage;

  /**
   * The support_ticket access control handler.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $supportTicketAccess;

  /**
   * A static cache of access checks.
   *
   * @var array
   */
  protected $access = array();

  /**
   * Constructs a new SupportTicketRevisionAccessCheck.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->supportTicketStorage = $entity_manager->getStorage('support_ticket');
    $this->supportTicketAccess = $entity_manager->getAccessControlHandler('support_ticket');
  }

  /**
   * Checks routing access for the support_ticket revision.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param int $support_ticket_revision
   *   (optional) The support_ticket revision ID. If not specified, but
   *   $support_ticket is, access is checked for that object's revision.
   * @param \Drupal\support_ticket\SupportTicketInterface $support_ticket
   *   (optional) A support_ticket object. Used for checking access to a
   *   support_ticket's default revision when $support_ticket_revision is
   *   unspecified. Ignored when $support_ticket_revision is specified. If neither
   *   $support_ticket_revision nor $support_ticket are specified, then access is
   *   denied.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, AccountInterface $account, $support_ticket_revision = NULL, SupportTicketInterface $support_ticket = NULL) {
    if ($support_ticket_revision) {
      $support_ticket = $this->supportTicketStorage->loadRevision($support_ticket_revision);
    }
    $operation = $route->getRequirement('_access_support_ticket_revision');
    return AccessResult::allowedIf($support_ticket && $this->checkAccess($support_ticket, $account, $operation))->cachePerPermissions();
  }

  /**
   * Checks support_ticket revision access.
   *
   * @param \Drupal\support_ticket\SupportTicketInterface $support_ticket
   *   The support_ticket to check.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   A user object representing the user for whom the operation is to be
   *   performed.
   * @param string $op
   *   (optional) The specific operation being checked. Defaults to 'view.'
   *
   * @return bool
   *   TRUE if the operation may be performed, FALSE otherwise.
   */
  public function checkAccess(SupportTicketInterface $support_ticket, AccountInterface $account, $op = 'view') {
    $map = array(
      'view' => 'view all revisions',
      'update' => 'revert all revisions',
      'delete' => 'delete all revisions',
    );
    $bundle = $support_ticket->bundle();
    $type_map = array(
      'view' => "view $bundle revisions",
      'update' => "revert $bundle revisions",
      'delete' => "delete $bundle revisions",
    );

    if (!$support_ticket || !isset($map[$op]) || !isset($type_map[$op])) {
      // If there was no support_ticket to check against, or the $op was not one of
      // the supported ones, we return access denied.
      return FALSE;
    }

    // Statically cache access by revision ID, language code, user account ID,
    // and operation.
    $langcode = $support_ticket->language()->getId();
    $cid = $support_ticket->getRevisionId() . ':' . $langcode . ':' . $account->id() . ':' . $op;

    if (!isset($this->access[$cid])) {
      // Perform basic permission checks first.
      if (!$account->hasPermission($map[$op]) && !$account->hasPermission($type_map[$op]) && !$account->hasPermission('administer support tickets')) {
        $this->access[$cid] = FALSE;
        return FALSE;
      }

      // There should be at least two revisions. If the vid of the given
      // support_ticket and the vid of the default revision differ, then we already
      // have two different revisions so there is no need for a separate database
      // check. Also, if you try to revert to or delete the default revision, that's
      // not good.
      if ($support_ticket->isDefaultRevision() && ($this->supportTicketStorage->countDefaultLanguageRevisions($support_ticket) == 1 || $op == 'update' || $op == 'delete')) {
        $this->access[$cid] = FALSE;
      }
      elseif ($account->hasPermission('administer support tickets')) {
        $this->access[$cid] = TRUE;
      }
      else {
        // First check the access to the default revision and finally, if the
        // support_ticket passed in is not the default revision then access to that,
        // too.
        $this->access[$cid] = $this->supportTicketAccess->access($this->supportTicketStorage->load($support_ticket->id()), $op, $account) && ($support_ticket->isDefaultRevision() || $this->supportTicketAccess->access($support_ticket, $op, $account));
      }
    }

    return $this->access[$cid];
  }

}
