<?php

/**
 * @file
 * Contains \Drupal\support_ticket\Plugin\views\field\Path.
 */

namespace Drupal\support_ticket\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * Field handler to present the path to the support ticket.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("support_ticket_path")
 */
class Path extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->additional_fields['stid'] = 'stid';
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['absolute'] = array('default' => FALSE);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['absolute'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Use absolute link (begins with "http://")'),
      '#default_value' => $this->options['absolute'],
      '#description' => $this->t('Enable this option to output an absolute link. Required if you want to use the path as a link destination (as in "output this field as a link" above).'),
      '#fieldset' => 'alter',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $this->addAdditionalFields();
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $stid = $this->getValue($values, 'stid');
    return \Drupal::url('entity.support_ticket.canonical', ['support_ticket' => $stid], ['absolute' => $this->options['absolute']]);
  }

}
