<?php

/**
 * @file
 * Contains \Drupal\support_ticket\Plugin\Action\UnlockSupportTicket.
 */

namespace Drupal\support_ticket\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Makes a support ticket not locked.
 *
 * @Action(
 *   id = "support_ticket_unlock_action",
 *   label = @Translation("Unlock selected ticket"),
 *   type = "support_ticket"
 * )
 */
class UnlockSupportTicket extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $entity->unlock = SUPPORT_TICKET_NOT_LOCKED;
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\support_ticket\SupportTicketInterface $object */
    $access = $object->access('update', $account, TRUE)
      // @todo: does this work?
      ->andIf($object->unlock->access('edit', $account, TRUE));

    return $return_as_object ? $access : $access->isAllowed();
  }

}
