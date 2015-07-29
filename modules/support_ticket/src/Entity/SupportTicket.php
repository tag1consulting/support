<?php

/**
 * @file
 * Contains \Drupal\support\Entity\SupportTicket.
 */

namespace Drupal\support\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;

/**
 * Defines the Support Ticket entity.
 *
 * @ConfigEntityType(
 *   id = "support_ticket",
 *   label = @Translation("Support ticket"),
 *   handlers = {
 *     "storage" = "Drupal\support\SupportTicketStorage", // @todo -- what do we do with this?
 *     "storage_schema" = "Drupal\support\SupportTicketStorageSchema", // @todo -- what do we do with this?
 *     "view_builder" = "Drupal\support\SupportTicketViewBuilder", // @todo -- what do we do with this?
 *     "access" = "Drupal\support\SupportTicketAccessControlHandler", // @todo -- what do we do with this?
 *     "views_data" = "Drupal\support\SupportTicketViewsData", // @todo -- what do we do with this?
 *     "form" = {
 *       "default" = "Drupal\support\SupportTicketForm",
 *       "delete" = "Drupal\support\Form\SupportTicketDeleteForm",
 *       "edit" = "Drupal\support\SupportTicketForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\support\Entity\SupportTicketRouteProvider", // @todo -- what do we do with this?
 *     },
 *     "list_builder" = "Drupal\support\SupportTicketListBuilder", // @todo -- what do we do with this?
 *   },
 *   base_table = "support_ticket",
 *   data_table = "support_ticket_field_data",
 *   revision_table = "support_ticket_revision",
 *   revision_data_table = "support_ticket_field_revision",
 *   config_prefix = "form", // @todo -- what is a config_prefix?
 *   entity_keys = {
 *     "id" = "stid",
 *     "revision" = "vid",
 *     "bundle" = "type", // @todo -- will we define a bundle?
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "status" = "status",
 *     "uid" = "uid",
 *   },
 *   admin_permission = "administer support ticket",
 *   bundle_of = "contact_message", // @todo -- do we need a bundle?
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "canonical" = "/support_ticket/{support_ticket}",
 *     "delete-form" = "/support_ticket/{support_ticket}/delete",
 *     "edit-form" = "/support_ticket/{support_ticket}/edit",
 *     "version-history" = "/support_ticket/{support_ticket}/revisions",
 *     "revision" = "/support_ticket/{support_ticket}/revisions/{support_ticket_revision}/view",
 *   }
 * )
 */
class SupportTicket extends ConfigEntityBase implements SupportTicketInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // If no owner has been set explicitly, make the current user the owner.
    if (!$this->getOwner()) {
      $this->setOwnerId(\Drupal::currentUser()->id());
    }
    // If no revision author has been set explicitly, make the support ticket owner
    // the revision author.
    if (!$this->getRevisionAuthor()) {
      $this->setRevisionAuthorId($this->getOwnerId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSaveRevision(EntityStorageInterface $storage, \stdClass $record) {
    parent::preSaveRevision($storage, $record);

    if (!$this->isNewRevision() && isset($this->original) && (!isset($record->revision_log) || $record->revision_log === '')) {
      // If we are updating an existing support ticket without adding a new revision,
      // we need to make sure $entity->revision_log is reset whenever it is empty.
      // Therefore, this code allows us to avoid clobbering an existing log
      // entry with an empty one.
      $record->revision_log = $this->original->revision_log->value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Reindex the support_ticket when it is updated. The support_ticket is
    // automatically indexed when it is added, simply by being added to the
    // support_ticket table.
    if ($update) {
      // @todo
      // support_ticket_reindex_support_ticket_search($this->id());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    parent::preDelete($storage, $entities);

    // Ensure that all support_tickets deleted are removed from the search index.
    if (\Drupal::moduleHandler()->moduleExists('search')) {
      foreach ($entities as $entity) {
        search_index_clear('support_ticket_search', $entity->stid->value);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->set('title', $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return $this->get('changed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? NODE_PUBLISHED : NODE_NOT_PUBLISHED);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->getEntityKey('uid');
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionCreationTime() {
    return $this->get('revision_timestamp')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionCreationTime($timestamp) {
    $this->set('revision_timestamp', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionAuthor() {
    return $this->get('revision_uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionAuthorId($uid) {
    $this->set('revision_uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['stid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Support Ticket ID'))
      ->setDescription(t('The support ticket ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The support ticket UUID.'))
      ->setReadOnly(TRUE);

    $fields['vid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Revision ID'))
      ->setDescription(t('The support ticket revision ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setDefaultValue('')
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -5,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The username of the support ticket author.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\support\Entity\SupportTicket::getCurrentUserId')
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the support ticket is published.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(TRUE);

    $fields['locked'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Locked status'))
      ->setDescription(t('A boolean indicating whether the support ticket is locked (only editable by an admin).'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(FALSE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the support ticket was created.'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'datetime_timestamp',
        'weight' => 10,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the support ticket was last edited.'))
      ->setRevisionable(TRUE)


    $fields['revision_timestamp'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Revision timestamp'))
      ->setDescription(t('The time that the current revision was created.'))
      ->setQueryable(FALSE)
      ->setRevisionable(TRUE);

    $fields['revision_uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Revision user ID'))
      ->setDescription(t('The user ID of the author of the current revision.'))
      ->setSetting('target_type', 'user')
      ->setQueryable(FALSE)
      ->setRevisionable(TRUE);

    $fields['revision_log'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Revision log message'))
      ->setDescription(t('Briefly describe the changes you have made.'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'string_textarea',
        'weight' => 25,
        'settings' => array(
          'rows' => 4,
        ),
      ));

    return $fields;
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return array(\Drupal::currentUser()->id());
  }

}
