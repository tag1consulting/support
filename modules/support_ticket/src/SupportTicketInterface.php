<?php

/**
 * @file
 * Contains \Drupal\support_ticket\SupportTicketInterface.
 */

namespace Drupal\support_ticket;

use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\UserInterface;

/**
 * Provides an interface defining a support ticket entity.
 */
interface SupportTicketInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the support ticket type.
   *
   * @return string
   *   The support ticket type.
   */
  public function getType();

  /**
   * Gets the support ticket title.
   *
   * @return string
   *   Title of the support ticket.
   */
  public function getTitle();

  /**
   * Sets the support ticket title.
   *
   * @param string $title
   *   The support ticket title.
   *
   * @return \Drupal\support_ticket\SupportTicketInterface
   *   The called support ticket entity.
   */
  public function setTitle($title);

  /**
   * Gets the support ticket creation timestamp.
   *
   * @return int
   *   Creation timestamp of the support ticket.
   */
  public function getCreatedTime();

  /**
   * Sets the support ticket creation timestamp.
   *
   * @param int $timestamp
   *   The support ticket creation timestamp.
   *
   * @return \Drupal\support_ticket\SupportTicketInterface
   *   The called support ticket entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the support ticket locked status.
   *
   * @return bool
   *   TRUE if the support ticket is locked.
   */
  public function isLocked();

  /**
   * Sets the support ticket locked status.
   *
   * @param bool $locked
   *   TRUE to lock this support ticket, FALSE to unlock this support ticket.
   *
   * @return \Drupal\support_ticket\SupportTicketInterface
   *   The called support ticket entity.
   */
  public function setLocked($locked);

  /**
   * Returns the support ticket published status indicator.
   *
   * Unpublished support tickets are only visible to their authors and to administrators.
   *
   * @return bool
   *   TRUE if the support ticket is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a support ticket.
   *
   * @param bool $published
   *   TRUE to set this support ticket to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\suport\SupportTicketInterface
   *   The called support ticket entity.
   */
  public function setPublished($published);

  /**
   * Gets the support ticket revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the support ticket revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\support_ticket\SupportTicketInterface
   *   The called support ticket entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the support ticket revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionAuthor();

  /**
   * Sets the support ticket revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\support_ticket\SupportTicketInterface
   *   The called support ticket entity.
   */
  public function setRevisionAuthorId($uid);
}
