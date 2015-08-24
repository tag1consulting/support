<?php

/**
 * @file
 * Contains \Drupal\support_ticket\Plugin\views\wizard\SupportTicketRevision.
 */

namespace Drupal\support_ticket\Plugin\views\wizard;

use Drupal\views\Plugin\views\wizard\WizardPluginBase;

/**
 * @todo: replace numbers with constants.
 */

/**
 * Tests creating support ticket revision views with the wizard.
 *
 * @ViewsWizard(
 *   id = "support_ticket_revision",
 *   base_table = "support_ticket_field_revision",
 *   title = @Translation("Support ticket revisions")
 * )
 */
class SupportTicketRevision extends WizardPluginBase {

  /**
   * Set the created column.
   */
  protected $createdColumn = 'changed';

  /**
   * Set default values for the filters.
   */
  protected $filters = array(
    'status' => array(
      'value' => TRUE,
      'table' => 'support_ticket_field_revision',
      'field' => 'status',
      'plugin_id' => 'boolean',
      'entity_type' => 'support_ticket',
      'entity_field' => 'status',
    )
  );

  /**
   * Overrides Drupal\views\Plugin\views\wizard\WizardPluginBase::rowStyleOptions().
   *
   * Support ticket revisions do not support full posts or teasers, so remove them.
   */
  protected function rowStyleOptions() {
    $options = parent::rowStyleOptions();
    unset($options['teasers']);
    unset($options['full_posts']);
    return $options;
  }

  /**
   * Overrides Drupal\views\Plugin\views\wizard\WizardPluginBase::defaultDisplayOptions().
   */
  protected function defaultDisplayOptions() {
    $display_options = parent::defaultDisplayOptions();

    // Add permission-based access control.
    $display_options['access']['type'] = 'perm';
    $display_options['access']['options']['perm'] = 'view all revisions';

    // Remove the default fields, since we are customizing them here.
    unset($display_options['fields']);

    /* Field: Support ticket revision: Created date */
    $display_options['fields']['changed']['id'] = 'changed';
    $display_options['fields']['changed']['table'] = 'support_ticket_field_revision';
    $display_options['fields']['changed']['field'] = 'changed';
    $display_options['fields']['changed']['entity_type'] = 'support_ticket';
    $display_options['fields']['changed']['entity_field'] = 'changed';
    $display_options['fields']['changed']['alter']['alter_text'] = FALSE;
    $display_options['fields']['changed']['alter']['make_link'] = FALSE;
    $display_options['fields']['changed']['alter']['absolute'] = FALSE;
    $display_options['fields']['changed']['alter']['trim'] = FALSE;
    $display_options['fields']['changed']['alter']['word_boundary'] = FALSE;
    $display_options['fields']['changed']['alter']['ellipsis'] = FALSE;
    $display_options['fields']['changed']['alter']['strip_tags'] = FALSE;
    $display_options['fields']['changed']['alter']['html'] = FALSE;
    $display_options['fields']['changed']['hide_empty'] = FALSE;
    $display_options['fields']['changed']['empty_zero'] = FALSE;
    $display_options['fields']['changed']['plugin_id'] = 'date';

    /* Field: Support ticket revision: Title */
    $display_options['fields']['title']['id'] = 'title';
    $display_options['fields']['title']['table'] = 'support_ticket_field_revision';
    $display_options['fields']['title']['field'] = 'title';
    $display_options['fields']['title']['entity_type'] = 'support_ticket';
    $display_options['fields']['title']['entity_field'] = 'title';
    $display_options['fields']['title']['label'] = '';
    $display_options['fields']['title']['alter']['alter_text'] = 0;
    $display_options['fields']['title']['alter']['make_link'] = 0;
    $display_options['fields']['title']['alter']['absolute'] = 0;
    $display_options['fields']['title']['alter']['trim'] = 0;
    $display_options['fields']['title']['alter']['word_boundary'] = 0;
    $display_options['fields']['title']['alter']['ellipsis'] = 0;
    $display_options['fields']['title']['alter']['strip_tags'] = 0;
    $display_options['fields']['title']['alter']['html'] = 0;
    $display_options['fields']['title']['hide_empty'] = 0;
    $display_options['fields']['title']['empty_zero'] = 0;
    $display_options['fields']['title']['settings']['link_to_entity'] = 0;
    $display_options['fields']['title']['plugin_id'] = 'field';
    return $display_options;
  }

}
