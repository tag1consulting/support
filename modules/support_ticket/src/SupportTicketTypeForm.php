<?php

/**
 * @file
 * Contains \Drupal\support_ticket\SupportTicketTypeForm.
 */

namespace Drupal\support_ticket;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\language\Entity\ContentLanguageSettings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for support ticket type forms.
 */
class SupportTicketTypeForm extends EntityForm {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs the SupportTicketTypeForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $type = $this->entity;
    if ($this->operation == 'add') {
      $form['#title'] = SafeMarkup::checkPlain($this->t('Add support ticket type'));
      $fields = $this->entityManager->getBaseFieldDefinitions('support_ticket');
      // Create a support ticket with a fake bundle using the type's UUID so that we can
      // get the default values for workflow settings.
      $support_ticket = $this->entityManager->getStorage('support_ticket')->create(array('support_ticket_type' => $type->uuid()));
    }
    else {
      $form['#title'] = $this->t('Edit %label support ticket type', array('%label' => $type->label()));
      $fields = $this->entityManager->getFieldDefinitions('support_ticket', $type->id());
      // Create a support_ticket to get the current values for workflow settings fields.
      $support_ticket = $this->entityManager->getStorage('support_ticket')->create(array('support_ticket_type' => $type->id()));
    }

    $form['name'] = array(
      '#title' => t('Name'),
      '#type' => 'textfield',
      '#default_value' => $type->label(),
      '#description' => t('The human-readable name of this support ticket type. This text will be displayed as part of the list on the <em>Add support ticket</em> page. This name must be unique.'), // @todo how to refer to this page?
      '#required' => TRUE,
      '#size' => 30,
    );

    $form['type'] = array(
      '#type' => 'machine_name',
      '#default_value' => $type->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#disabled' => $type->isLocked(),
      '#machine_name' => array(
        'exists' => ['Drupal\support_ticket\Entity\SupportTicketType', 'load'],
        'source' => array('name'),
      ),
      '#description' => t('A unique machine-readable name for this support ticket type. It must only contain lowercase letters, numbers, and underscores. This name will be used for constructing the URL of the %support-ticket-add page, in which underscores will be converted into hyphens.', array(
        '%support-ticket-add' => t('Add support ticket'),
      )),
    );

    $form['description'] = array(
      '#title' => t('Description'),
      '#type' => 'textarea',
      '#default_value' => $type->getDescription(),
      '#description' => t('Describe this support ticket type. The text will be displayed on the <em>Add support ticket</em> page.'), // @todo how to refer to this page?
    );

    $form['additional_settings'] = array( // @todo, is this needed?
      '#type' => 'vertical_tabs',
      '#attached' => array(
        'library' => array('support_ticket/drupal.support_ticket_types'),
      ),
    );

    $form['submission'] = array(
      '#type' => 'details',
      '#title' => t('Submission form settings'),
      '#group' => 'additional_settings',
      '#open' => TRUE,
    );
    $form['submission']['title_label'] = array(
      '#title' => t('Title field label'),
      '#type' => 'textfield',
      '#default_value' => $fields['title']->getLabel(),
      '#required' => TRUE,
    );
    $form['submission']['preview_mode'] = array(
      '#type' => 'radios',
      '#title' => t('Preview before submitting'),
      '#default_value' => $type->getPreviewMode(),
      '#options' => array(
        DRUPAL_DISABLED => t('Disabled'),
        DRUPAL_OPTIONAL => t('Optional'),
        DRUPAL_REQUIRED => t('Required'),
      ),
    );
    $form['submission']['help']  = array(
      '#type' => 'textarea',
      '#title' => t('Explanation or submission guidelines'),
      '#default_value' => $type->getHelp(),
      '#description' => t('This text will be displayed at the top of the page when creating or editing support tickets of this type.'),
    );
    $form['workflow'] = array(
      '#type' => 'details',
      '#title' => t('Publishing options'),
      '#group' => 'additional_settings',
    );
    $workflow_options = array(
      'status' => $support_ticket->status->value,
      'locked' => $support_ticket->locked->value,
      'revision' => $type->isNewRevision(),
    );
    // Prepare workflow options to be used for 'checkboxes' form element.
    $keys = array_keys(array_filter($workflow_options));
    $workflow_options = array_combine($keys, $keys);
    $form['workflow']['options'] = array('#type' => 'checkboxes',
      '#title' => t('Default options'),
      '#default_value' => $workflow_options,
      '#options' => array(
        'status' => t('Published'),
        'locked' => t('Locked'),
        'revision' => t('Create new revision'),
      ),
      '#description' => t('Users with the <em>Administer support tickets</em> permission will be able to override these options.'),
    );
    if ($this->moduleHandler->moduleExists('language')) {
      $form['language'] = array(
        '#type' => 'details',
        '#title' => t('Language settings'),
        '#group' => 'additional_settings',
      );

      $language_configuration = ContentLanguageSettings::loadByEntityTypeBundle('support_ticket', $type->id());
      $form['language']['language_configuration'] = array(
        '#type' => 'language_configuration',
        '#entity_information' => array(
          'entity_type' => 'support_ticket',
          'bundle' => $type->id(),
        ),
        '#default_value' => $language_configuration,
      );
    }
    $form['display'] = array(
      '#type' => 'details',
      '#title' => t('Display settings'),
      '#group' => 'additional_settings',
    );
    $form['display']['display_submitted'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display author and date information'),
      '#default_value' => $type->displaySubmitted(),
      '#description' => t('Author username and publish date will be displayed.'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = t('Save support ticket type');
    $actions['delete']['#value'] = t('Delete support ticket type');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $id = trim($form_state->getValue('type'));
    // '0' is invalid, since elsewhere we check it using empty().
    if ($id == '0') {
      $form_state->setErrorByName('type', $this->t("Invalid machine-readable name. Enter a name other than %invalid.", array('%invalid' => $id)));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $type = $this->entity;
    $type->setNewRevision($form_state->getValue(array('options', 'revision')));
    $type->set('type', trim($type->id()));
    $type->set('name', trim($type->label()));

    $status = $type->save();

    $t_args = array('%name' => $type->label());

    if ($status == SAVED_UPDATED) {
      drupal_set_message(t('The support ticket type %name has been updated.', $t_args));
    }
    elseif ($status == SAVED_NEW) {
      support_ticket_add_body_field($type); // @todo
      drupal_set_message(t('The support ticket type %name has been added.', $t_args));
      $context = array_merge($t_args, array('link' => $type->link($this->t('View'), 'collection')));
      $this->logger('support_ticket')->notice('Added support ticket type %name.', $context);
    }

    $fields = $this->entityManager->getFieldDefinitions('support_ticket', $type->id());
    // Update title field definition.
    $title_field = $fields['title'];
    $title_label = $form_state->getValue('title_label');
    if ($title_field->getLabel() != $title_label) {
      $title_field->getConfig($type->id())->setLabel($title_label)->save();
    }
    // Update workflow options.
    $support_ticket = $this->entityManager->getStorage('support_ticket')->create(array('support_ticket_type' => $type->id()));
    foreach (array('status', 'locked')  as $field_name) {
      $value = (bool) $form_state->getValue(['options', $field_name]);
      if ($support_ticket->$field_name->value != $value) {
        $fields[$field_name]->getConfig($type->id())->setDefaultValue($value)->save();
      }
    }

    $this->entityManager->clearCachedFieldDefinitions();
    $form_state->setRedirectUrl($type->urlInfo('collection'));
  }

}
