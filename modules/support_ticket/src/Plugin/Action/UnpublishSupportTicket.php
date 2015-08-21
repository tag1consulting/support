<?php

/**
 * @file
 * Contains \Drupal\support_ticket\Plugin\Action\UnpublishSupportTicket.
 */

namespace Drupal\support_ticket\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Unpublishes a support ticket.
 *
 * @Action(
 *   id = "support_ticket_unpublish_action",
 *   label = @Translation("Unpublish selected ticket"),
 *   type = "support_ticket"
 * )
 */
class UnpublishSupportTicket extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $entity->status = SUPPORT_TICKET_NOT_PUBLISHED;
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\support_ticket\SupportTicketInterface $object */
    $access = $object->access('update', $account, TRUE)
      ->andIf($object->status->access('edit', $account, TRUE));

    return $return_as_object ? $access : $access->isAllowed();
  }

}
