<?php

/**
 * @file
 * Contains \Drupal\support_ticket\Plugin\Action\LockSupportTicket.
 */

namespace Drupal\support_ticket\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Lock a support ticket.
 *
 * @Action(
 *   id = "support_ticket_lock_action",
 *   label = @Translation("Lock selected support ticket"),
 *   type = "support_ticket"
 * )
 */
class LockSupportTicket extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $entity->lock = SUPPORT_TICKET_LOCKED;
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\support_ticket\SupportTicketInterface $object */
    $access = $object->access('update', $account, TRUE)
      // @todo: does this work?
      ->andif($object->locked->access('edit', $account, TRUE));
    return $return_as_object ? $access : $access->isAllowed();
  }

}
