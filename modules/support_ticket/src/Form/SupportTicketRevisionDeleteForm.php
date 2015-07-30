<?php

/**
 * @file
 * Contains \Drupal\support_ticket\Form\SupportTicketRevisionDeleteForm.
 */

namespace Drupal\support_ticket\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\support_ticket\SupportInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for reverting a support ticket revision.
 */
class SupportTicketRevisionDeleteForm extends ConfirmFormBase {

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
   * The support ticket type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $supportTicketTypeStorage;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new SupportTicketRevisionDeleteForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $support_ticket_storage
   *   The support ticket storage.
   * @param \Drupal\Core\Entity\EntityStorageInterface $support_ticket_type_storage
   *   The support ticket type storage.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(EntityStorageInterface $support_ticket_storage, EntityStorageInterface $support_ticket_type_storage, Connection $connection) {
    $this->supportTicketStorage = $support_ticket_storage;
    $this->supportTicketTypeStorage = $support_ticket_type_storage;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_manager = $container->get('entity.manager');
    return new static(
      $entity_manager->getStorage('support_ticket'),
      $entity_manager->getStorage('support_ticket_type'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'support_ticket_revision_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete the revision from %revision-date?', array('%revision-date' => format_date($this->revision->getRevisionCreationTime())));
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
    return t('Delete');
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
    $this->supportTicketStorage->deleteRevision($this->revision->getRevisionId());

    $this->logger('content')->notice('@type: deleted %title revision %revision.', array('@type' => $this->revision->bundle(), '%title' => $this->revision->label(), '%revision' => $this->revision->getRevisionId()));
    $support_ticket_type = $this->supportTicketTypeStorage->load($this->revision->bundle())->label();
    drupal_set_message(t('Revision from %revision-date of @type %title has been deleted.', array('%revision-date' => format_date($this->revision->getRevisionCreationTime()), '@type' => $support_ticket_type, '%title' => $this->revision->label())));
    $form_state->setRedirect(
      'entity.support_ticket.canonical',
      array('support_ticket' => $this->revision->id())
    );
    if ($this->connection->query('SELECT COUNT(DISTINCT vid) FROM {support_ticket_field_revision} WHERE stid = :stid', array(':stid' => $this->revision->id()))->fetchField() > 1) {
      $form_state->setRedirect(
        'entity.support_ticket.version_history',
        array('support_ticket' => $this->revision->id())
      );
    }
  }

}
