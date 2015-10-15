<?php

/**
 * @file
 * Contains \Drupal\support_ticket\SupportTicketTypeAccessControlHandler.
 */

namespace Drupal\support_ticket;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the support ticket type entity type.
 *
 * @see \Drupal\support_ticket\Entity\SupportTicketType
 */
class SupportTicketTypeAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($operation == 'delete') {
      if ($entity->isLocked()) {
        return AccessResult::forbidden()->cacheUntilEntityChanges($entity);
      }
      else {
        return parent::checkAccess($entity, $operation, $account)->cacheUntilEntityChanges($entity);
      }
    }
    return parent::checkAccess($entity, $operation, $account);
  }

}
