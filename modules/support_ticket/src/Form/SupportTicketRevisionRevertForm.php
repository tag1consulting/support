<?php

/**
 * @file
 * Contains \Drupal\support_ticket\Form\SupportTicketRevisionRevertForm.
 */

namespace Drupal\support_ticket\Form;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\support_ticket\SupportTicketInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for reverting a support ticket revision.
 */
class SupportTicketRevisionRevertForm extends ConfirmFormBase {

  /**
   * The support ticket revision.
   *
   * @var \Drupal\support_ticket\SupportTicketInterface
   */
  protected $revision;

  /**
   * The support ticket storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $supportTicketStorage;

  /**
   * Constructs a new SupportTicketRevisionRevertForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $support_ticket_storage
   *   The support_ticket storage.
   */
  public function __construct(EntityStorageInterface $support_ticket_storage) {
    $this->supportTicketStorage = $support_ticket_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('support_ticket')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'support_ticket_revision_revert_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to revert to the revision from %revision-date?', array('%revision-date' => format_date($this->revision->getRevisionCreationTime())));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.support_ticket.version_history', array('support_ticket' => $this->revision->id()));
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Revert');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $support_ticket_revision = NULL) {
    $this->revision = $this->supportTicketStorage->loadRevision($support_ticket_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $revision = $this->prepareRevertedRevision($this->revision);

    // The revision timestamp will be updated when the revision is saved. Keep the
    // original one for the confirmation message.
    $original_revision_timestamp = $revision->getRevisionCreationTime();
    $revision->revision_log = t('Copy of the revision from %date.', array('%date' => format_date($original_revision_timestamp)));

    $revision->save();

    $this->logger('content')->notice('@type: reverted %title revision %revision.', array('@type' => $this->revision->bundle(), '%title' => $this->revision->label(), '%revision' => $this->revision->getRevisionId()));
    drupal_set_message(t('@type %title has been reverted to the revision from %revision-date.', array('@type' => support_ticket_get_type_label($this->revision), '%title' => $this->revision->label(), '%revision-date' => format_date($original_revision_timestamp))));
    $form_state->setRedirect(
      'entity.support_ticket.version_history',
      array('support_ticket' => $this->revision->id())
    );
  }

  /**
   * Prepares a revision to be reverted.
   *
   * @param \Drupal\support_ticket\SupportTicketInterface $revision
   *   The revision to be reverted.
   *
   * @return \Drupal\support_ticket\SupportTicketInterface
   *   The prepared revision ready to be stored.
   */
  protected function prepareRevertedRevision(SupportTicketInterface $revision) {
    /** @var \Drupal\support_ticket\SupportTicketInterface $default_revision */
    $default_revision = $this->supportTicketStorage->load($revision->id());

    // If the entity is translated, make sure only translations affected by the
    // specified revision are reverted.
    $languages = $default_revision->getTranslationLanguages();
    if (count($languages) > 1) {
      foreach ($languages as $langcode => $language) {
        if ($revision->hasTranslation($langcode) && !$revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
          $revision_translation = $revision->getTranslation($langcode);
          $default_translation = $default_revision->getTranslation($langcode);
          foreach ($default_revision->getFieldDefinitions() as $field_name => $definition) {
            if ($definition->isTranslatable()) {
              $revision_translation->set($field_name, $default_translation->get($field_name)->getValue());
            }
          }
        }
      }
    }

    $revision->setNewRevision();
    $revision->isDefaultRevision(TRUE);

    return $revision;
  }

}
