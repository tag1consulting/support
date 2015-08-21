<?php

/**
 * @file
 * Contains \Drupal\support_ticket\Access\SupportTicketAddAccessCheck.
 */

namespace Drupal\support_ticket\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\support_ticket\SupportTicketTypeInterface;

/**
 * Determines access to for support_ticket add pages.
 *
 * @ingroup support_ticket_access
 */
class SupportTicketAddAccessCheck implements AccessInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a EntityCreateAccessCheck object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * Checks access to the support_ticket add page for the support_ticket type.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\support_ticket\SupportTicketTypeInterface $support_ticket_type
   *   (optional) The support_ticket type. If not specified, access is allowed if
   *   there exists at least one support_ticket type for which the user may create
   *   a support_ticket.
   *
   * @return string
   *   A \Drupal\Core\Access\AccessInterface constant value.
   */
  public function access(AccountInterface $account, SupportTicketTypeInterface $support_ticket_type = NULL) {
    $access_control_handler = $this->entityManager->getAccessControlHandler('support_ticket');
    // If checking whether a support_ticket of a particular type may be created.
    if ($account->hasPermission('administer support ticket types')) {
      return AccessResult::allowed()->cachePerPermissions();
    }
    if ($support_ticket_type) {
      return $access_control_handler->createAccess($support_ticket_type->id(), $account, [], TRUE);
    }
    // If checking whether a support_ticket of any type may be created.
    foreach ($this->entityManager->getStorage('support_ticket_type')->loadMultiple() as $support_ticket_type) {
      if (($access = $access_control_handler->createAccess($support_ticket_type->id(), $account, [], TRUE)) && $access->isAllowed()) {
        return $access;
      }
    }

    // No opinion.
    return AccessResult::neutral();
  }

}
