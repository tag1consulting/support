<?php

/**
 * @file
 * Contains \Drupal\support_ticket\Access\SupportTicketPreviewAccessCheck.
 */

namespace Drupal\support_ticket\Access;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\support_ticket\SupportTicketInterface;

/**
 * Determines access to support_ticket previews.
 *
 * @ingroup support_ticket_access
 */
class SupportTicketPreviewAccessCheck implements AccessInterface {

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
   * Checks access to the support_ticket preview page.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\support_ticket\SupportTicketInterface $support_ticket_preview
   *   The support_ticket that is being previewed.
   *
   * @return string
   *   A \Drupal\Core\Access\AccessInterface constant value.
   */
  public function access(AccountInterface $account, SupportTicketInterface $support_ticket_preview) {
    if ($support_ticket_preview->isNew()) {
      $access_controller = $this->entityManager->getAccessControlHandler('support_ticket');
      return $access_controller->createAccess($support_ticket_preview->bundle(), $account, [], TRUE);
    }
    else {
      return $support_ticket_preview->access('update', $account, TRUE);
    }
  }

}
