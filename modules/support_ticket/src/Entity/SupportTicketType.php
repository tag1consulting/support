<?php

/**
 * @file
 * Contains \Drupal\support_ticket\Entity\SupportTicketType.
 */

namespace Drupal\support_ticket\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\support_ticket\SupportTicketTypeInterface;

/**
 * Defines the SupportTicket type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "support_ticket_type",
 *   label = @Translation("Support ticket type"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\support_ticket\SupportTicketTypeForm",
 *       "edit" = "Drupal\support_ticket\SupportTicketTypeForm",
 *       "delete" = "Drupal\support_ticket\Form\SupportTicketTypeDeleteConfirm"
 *     },
 *     "list_builder" = "Drupal\support_ticket\SupportTicketTypeListBuilder",
 *   },
 *   admin_permission = "administer support ticket types",
 *   config_prefix = "type",
 *   bundle_of = "support_ticket",
 *   entity_keys = {
 *     "id" = "type",
 *     "label" = "name"
 *   },
 *   links = {
 *     "edit-form" = "/admin/support/ticket-types/manage/{support_ticket_type}",
 *     "delete-form" = "/admin/support/ticket-types/manage/{support_ticket_type}/delete",
 *     "collection" = "/admin/support/ticket-types",
 *   },
 *   config_export = {
 *     "name",
 *     "type",
 *     "description",
 *     "help",
 *     "new_revision",
 *     "preview_mode",
 *     "display_submitted",
 *   }
 * )
 */
class SupportTicketType extends ConfigEntityBundleBase implements SupportTicketTypeInterface {

  /**
   * The machine name of this support ticket type.
   *
   * @var string
   */
  protected $type;

  /**
   * The human-readable name of the support ticket type.
   *
   * @var string
   */
  protected $name;

  /**
   * A brief description of this support ticket type.
   *
   * @var string
   */
  protected $description;

  /**
   * Help information shown to the user when creating a Support Ticket of this type.
   *
   * @var string
   */
  protected $help;

  /**
   * Default value of the 'Create new revision' checkbox of this support ticket type.
   *
   * @var bool
   */
  protected $new_revision = FALSE;

  /**
   * The preview mode.
   *
   * @var int
   */
  protected $preview_mode = DRUPAL_OPTIONAL;

  /**
   * Display setting for author and date Submitted by post information.
   *
   * @var bool
   */
  protected $display_submitted = TRUE;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    $locked = \Drupal::state()->get('support_ticket.type.locked');
    return isset($locked[$this->id()]) ? $locked[$this->id()] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isNewRevision() {
    return $this->new_revision;
  }

  /**
   * {@inheritdoc}
   */
  public function setNewRevision($new_revision) {
    $this->new_revision = $new_revision;
  }

  /**
   * {@inheritdoc}
   */
  public function displaySubmitted() {
    return $this->display_submitted;
  }

  /**
   * {@inheritdoc}
   */
  public function setDisplaySubmitted($display_submitted) {
    $this->display_submitted = $display_submitted;
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviewMode() {
    return $this->preview_mode;
  }

  /**
   * {@inheritdoc}
   */
  public function setPreviewMode($preview_mode) {
    $this->preview_mode = $preview_mode;
  }

  /**
   * {@inheritdoc}
   */
  public function getHelp() {
    return $this->help;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    if ($update && $this->getOriginalId() != $this->id()) {
      $update_count = support_ticket_type_update_support_tickets($this->getOriginalId(), $this->id());
      if ($update_count) {
        drupal_set_message(\Drupal::translation()->formatPlural($update_count,
          'Changed the support ticket type of 1 ticket from %old-type to %type.',
          'Changed the support ticket type of @count tickets from %old-type to %type.',
          array(
            '%old-type' => $this->getOriginalId(),
            '%type' => $this->id(),
          )));
      }
    }
    if ($update) {
      // Clear the cached field definitions as some settings affect the field
      // definitions.
      $this->entityManager()->clearCachedFieldDefinitions();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    // Clear the support ticket type cache to reflect the removal.
    $storage->resetCache(array_keys($entities));
  }

}
