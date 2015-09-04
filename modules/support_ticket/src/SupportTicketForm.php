<?php

/**
 * @file
 * Contains \Drupal\support_ticket\SupportTicketForm.
 */

namespace Drupal\support_ticket;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the support ticket edit forms.
 */
class SupportTicketForm extends ContentEntityForm {

  /**
   * The tempstore factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * Whether this support ticket has been previewed or not.
   */
  protected $hasBeenPreviewed = FALSE;

  /**
   * Constructs a ContentEntityForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The factory for the temp store object.
   */
  public function __construct(EntityManagerInterface $entity_manager, PrivateTempStoreFactory $temp_store_factory) {
    parent::__construct($entity_manager);
    $this->tempStoreFactory = $temp_store_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('user.private_tempstore')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntity() {
    /** @var \Drupal\support_ticket\SupportTicketInterface $support_ticket */
    $support_ticket = $this->entity;

    if (!$support_ticket->isNew()) {
      // Remove the revision log message from the original support ticket entity.
      $support_ticket->revision_log = NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    // Try to restore from temp store, this must be done before calling
    // parent::form().
    $uuid = $this->entity->uuid();
    $store = $this->tempStoreFactory->get('support_ticket_preview');

    // If the user is creating a new support_ticket, the UUID is passed in the request.
    if ($request_uuid = \Drupal::request()->query->get('uuid')) {
      $uuid = $request_uuid;
    }

    if ($preview = $store->get($uuid)) {
      /** @var $preview \Drupal\Core\Form\FormStateInterface */
      $form_state = $preview;

      // Rebuild the form.
      $form_state->setRebuild();
      $this->entity = $preview->getFormObject()->getEntity();
      unset($this->entity->in_preview);

      // Remove the stale temp store entry for existing support tickets.
      if (!$this->entity->isNew()) {
        $store->delete($uuid);
      }

      $this->hasBeenPreviewed = TRUE;
    }

    /** @var \Drupal\support_ticket\SupportTicketInterface $support_ticket */
    $support_ticket = $this->entity;

    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('<em>Edit @support_ticket_type</em> @title', array('@support_ticket_type' => support_ticket_get_type_label($support_ticket), '@title' => $support_ticket->label()));
    }

    $current_user = $this->currentUser();

    // Changed must be sent to the client, for later overwrite error checking.
    $form['changed'] = array(
      '#type' => 'hidden',
      '#default_value' => $support_ticket->getChangedTime(),
    );

    $form['advanced'] = array(
      '#type' => 'vertical_tabs',
      '#attributes' => array('class' => array('entity-meta')),
      '#weight' => 99,
    );
    $form = parent::form($form, $form_state);

    // Add a revision_log field if the "Create new revision" option is checked,
    // or if the current user has the ability to check that option.
    $form['revision_information'] = array(
      '#type' => 'details',
      '#group' => 'advanced',
      '#title' => t('Revision information'),
      // Open by default when "Create new revision" is checked.
      '#open' => $support_ticket->isNewRevision(),
      '#attributes' => array(
        'class' => array('support-ticket-form-revision-information'),
      ),
      '#attached' => array(
        'library' => array('support_ticket/drupal.support_ticket'),
      ),
      '#weight' => 20,
      '#optional' => TRUE,
    );

    $form['revision'] = array(
      '#type' => 'checkbox',
      '#title' => t('Create new revision'),
      '#default_value' => $support_ticket->support_ticket_type->entity->isNewRevision(),
      '#access' => $current_user->hasPermission('administer support tickets'),
      '#group' => 'revision_information',
    );

    $form['revision_log'] += array(
      '#states' => array(
        'visible' => array(
          ':input[name="revision"]' => array('checked' => TRUE),
        ),
      ),
      '#group' => 'revision_information',
    );

    // Support ticket author information for administrators.
    $form['author'] = array(
      '#type' => 'details',
      '#title' => t('Authoring information'),
      '#group' => 'advanced',
      '#attributes' => array(
        'class' => array('support-ticket-form-author'),
      ),
      '#attached' => array(
        'library' => array('support_ticket/drupal.support_ticket'),
      ),
      '#weight' => 90,
      '#optional' => TRUE,
    );

    if (isset($form['uid'])) {
      $form['uid']['#group'] = 'author';
    }

    if (isset($form['created'])) {
      $form['created']['#group'] = 'author';
    }

    // Support ticket options for administrators.
    $form['options'] = array(
      '#type' => 'details',
      '#title' => t('Locking options'),
      '#group' => 'advanced',
      '#attributes' => array(
        'class' => array('support-ticket-form-options'),
      ),
      '#attached' => array(
        'library' => array('support_ticket/drupal.support_ticket'),
      ),
      '#weight' => 95,
      '#optional' => TRUE,
    );

    if (isset($form['locked'])) {
      $form['locked']['#group'] = 'options';
    }

    $form['#attached']['library'][] = 'support_ticket/form';

    $form['#entity_builders']['update_status'] = [$this, 'updateStatus'];

    return $form;
  }

  /**
   * Entity builder updating the support_ticket status with the submitted value.
   *
   * @param string $entity_type_id
   *   The entity type identifier.
   * @param \Drupal\support_ticket\SupportTicketInterface $support_ticket
   *   The support_ticket updated with the submitted values.
   * @param array $form
   *   The complete form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\support_ticket\SupportTicketForm::form()
   */
  function updateStatus($entity_type_id, SupportTicketInterface $support_ticket, array $form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();
    if (isset($element['#published_status'])) {
      $support_ticket->setPublished($element['#published_status']);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $element = parent::actions($form, $form_state);
    $support_ticket = $this->entity;
    $preview_mode = $support_ticket->support_ticket_type->entity->getPreviewMode();

    $element['submit']['#access'] = $preview_mode != DRUPAL_REQUIRED || $this->hasBeenPreviewed;

    // If saving is an option, privileged users get dedicated form submit
    // buttons to adjust the publishing status while saving in one go.
    // @todo This adjustment makes it close to impossible for contributed
    //   modules to integrate with "the Save operation" of this form. Modules
    //   need a way to plug themselves into 1) the ::submit() step, and
    //   2) the ::save() step, both decoupled from the pressed form button.
    if ($element['submit']['#access'] && \Drupal::currentUser()->hasPermission('administer support tickets')) {
      // isNew | prev status » default   & publish label             & unpublish label
      // 1     | 1           » publish   & Save and publish          & Save as unpublished
      // 1     | 0           » unpublish & Save and publish          & Save as unpublished
      // 0     | 1           » publish   & Save and keep published   & Save and unpublish
      // 0     | 0           » unpublish & Save and keep unpublished & Save and publish

      // Add a "Publish" button.
      $element['publish'] = $element['submit'];
      // If the "Publish" button is clicked, we want to update the status to "published".
      $element['publish']['#published_status'] = TRUE;
      $element['publish']['#dropbutton'] = 'save';
      if ($support_ticket->isNew()) {
        $element['publish']['#value'] = t('Save and publish');
      }
      else {
        $element['publish']['#value'] = $support_ticket->isPublished() ? t('Save and keep published') : t('Save and publish');
      }
      $element['publish']['#weight'] = 0;

      // Add a "Unpublish" button.
      $element['unpublish'] = $element['submit'];
      // If the "Unpublish" button is clicked, we want to update the status to "unpublished".
      $element['unpublish']['#published_status'] = FALSE;
      $element['unpublish']['#dropbutton'] = 'save';
      if ($support_ticket->isNew()) {
        $element['unpublish']['#value'] = t('Save as unpublished');
      }
      else {
        $element['unpublish']['#value'] = !$support_ticket->isPublished() ? t('Save and keep unpublished') : t('Save and unpublish');
      }
      $element['unpublish']['#weight'] = 10;

      // If already published, the 'publish' button is primary.
      if ($support_ticket->isPublished()) {
        unset($element['unpublish']['#button_type']);
      }
      // Otherwise, the 'unpublish' button is primary and should come first.
      else {
        unset($element['publish']['#button_type']);
        $element['unpublish']['#weight'] = -10;
      }

      // Remove the "Save" button.
      $element['submit']['#access'] = FALSE;
    }

    $element['preview'] = array(
      '#type' => 'submit',
      '#access' => $preview_mode != DRUPAL_DISABLED,
      '#value' => t('Preview'),
      '#weight' => 20,
      '#submit' => array('::submitForm', '::preview'),
    );

    $element['delete']['#weight'] = 100;

    return $element;
  }

  /**
   * {@inheritdoc}
   *
   * Updates the support_ticket object by processing the submitted values.
   *
   * This function can be called by a "Next" button of a wizard to update the
   * form state's entity with the current step's values before proceeding to the
   * next step.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Build the support_ticket object from the submitted values.
    parent::submitForm($form, $form_state);
    $support_ticket = $this->entity;

    // Save as a new revision if requested to do so.
    if (!$form_state->isValueEmpty('revision') && $form_state->getValue('revision') != FALSE) {
      $support_ticket->setNewRevision();
      // If a new revision is created, save the current user as revision author.
      $support_ticket->setRevisionCreationTime(REQUEST_TIME);
      $support_ticket->setRevisionAuthorId(\Drupal::currentUser()->id());
    }
    else {
      $support_ticket->setNewRevision(FALSE);
    }
  }

  /**
   * Form submission handler for the 'preview' action.
   *
   * @param $form
   *   An associative array containing the structure of the form.
   * @param $form_state
   *   The current state of the form.
   */
  public function preview(array $form, FormStateInterface $form_state) {
    $store = $this->tempStoreFactory->get('support_ticket_preview');
    $this->entity->in_preview = TRUE;
    $store->set($this->entity->uuid(), $form_state);
    $form_state->setRedirect('entity.support_ticket.preview', array(
      'support_ticket_preview' => $this->entity->uuid(),
      'view_mode_id' => 'default',
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\support_ticket\SupportTicketInterface $entity */
    $entity = parent::buildEntity($form, $form_state);
    if (!empty($form_state->getValue('uid')[0]['target_id']) && $account = User::load($form_state->getValue('uid')[0]['target_id'])) {
      $entity->setOwnerId($account->id());
    }
    else {
      $entity->setOwnerId(0);
    }
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $support_ticket = $this->entity;
    $insert = $support_ticket->isNew();
    $support_ticket->save();
    $support_ticket_link = $support_ticket->link($this->t('View'));
    $context = array('@support_ticket_type' => $support_ticket->getType(), '%title' => $support_ticket->label(), 'link' => $support_ticket_link);
    $t_args = array('@support_ticket_type' => support_ticket_get_type_label($support_ticket), '%title' => $support_ticket->label());

    if ($insert) {
      $this->logger('content')->notice('@support_ticket_type: added %title.', $context);
      drupal_set_message(t('@support_ticket_type %title has been created.', $t_args));
    }
    else {
      $this->logger('content')->notice('@support_ticket_type: updated %title.', $context);
      drupal_set_message(t('@support_ticket_type %title has been updated.', $t_args));
    }

    if ($support_ticket->id()) {
      $form_state->setValue('stid', $support_ticket->id());
      $form_state->set('stid', $support_ticket->id());
      $form_state->setRedirect(
        'entity.support_ticket.canonical',
        array('support_ticket' => $support_ticket->id())
      );

      // Remove the preview entry from the temp store, if any.
      $store = $this->tempStoreFactory->get('support_ticket_preview');
      $store->delete($support_ticket->uuid());
    }
    else {
      // In the unlikely case something went wrong on save, the support_ticket will be
      // rebuilt and support_ticket form redisplayed the same way as in preview.
      drupal_set_message(t('The post could not be saved.'), 'error');
      $form_state->setRebuild();
    }
  }

}
