<?php

/**
 * @file
 * Contains \Drupal\support_ticket\SupportTicketStorage.
 */

namespace Drupal\support_ticket;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Defines the controller class for support tickets.
 *
 * This extends the base storage class, adding required special handling for
 * support ticket entities.
 */
class SupportTicketStorage extends SqlContentEntityStorage implements SupportTicketStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(SupportTicketInterface $support_ticket) {
    return $this->database->query(
      'SELECT vid FROM {support_ticket_revision} WHERE stid=:stid ORDER BY vid',
      array(':stid' => $support_ticket->id())
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {support_ticket_field_revision} WHERE uid = :uid ORDER BY vid',
      array(':uid' => $account->id())
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(SupportTicketInterface $support_ticket) {
    return $this->database->query('SELECT COUNT(*) FROM {support_ticket_field_revision} WHERE stid = :stid AND default_langcode = 1', array(':stid' => $support_ticket->id()))->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function updateType($old_type, $new_type) {
    return $this->database->update('support_ticket')
      ->fields(array('support_ticket_type' => $new_type))
      ->condition('support_ticket_type', $old_type)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('support_ticket_revision')
      ->fields(array('langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED))
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
