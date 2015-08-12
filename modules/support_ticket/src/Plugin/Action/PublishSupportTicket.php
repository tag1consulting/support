<?php

/**
 * @file
 * Contains \Drupal\support_ticket\Plugin\Action\PublishSupportTicket.
 */

namespace Drupal\support_ticket\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Publishes a support ticket.
 *
 * @Action(
 *   id = "support_ticket_publish_action",
 *   label = @Translation("Publish selected ticket"),
 *   type = "support_ticket"
 * )
 */
class PublishSupportTicket extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $entity->status = SUPPORT_TICKET_PUBLISHED;
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\support_ticket\SupportTicketInterface $object */
    $result = $object->access('update', $account, TRUE)
      ->andIf($object->status->access('edit', $account, TRUE));

    return $return_as_object ? $result : $result->isAllowed();
  }

}
