<?php

/**
 * @file
 * Contains \Drupal\support_ticket\SupportTicketStorageInterface.
 */

namespace Drupal\support_ticket;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an interface for support ticket entity storage classes.
 */
interface SupportTicketStorageInterface extends EntityStorageInterface {

  /**
   * Gets a list of support ticket revision IDs for a specific support ticket.
   *
   * @param \Drupal\support_ticket\SupportTicketInterface
   *   The support ticket entity.
   *
   * @return int[]
   *   Support ticket revision IDs (in ascending order).
   */
  public function revisionIds(SupportTicketInterface $support_ticket);

  /**
   * Gets a list of revision IDs having a given user as support ticket author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Support ticket revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\support_ticket\SupportTicketInterface
   *   The support ticket entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(SupportTicketInterface $support_ticket);

  /**
   * Updates all support tickets of one type to be of another type.
   *
   * @param string $old_type
   *   The current support ticket type of the support tickets.
   * @param string $new_type
   *   The new support ticket type of the support tickets.
   *
   * @return int
   *   The number of support tickets whose support ticket type field was modified.
   */
  public function updateType($old_type, $new_type);

  /**
   * Unsets the language for all support tickets with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *  The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);
}
