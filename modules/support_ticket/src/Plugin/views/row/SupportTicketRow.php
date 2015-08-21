<?php

/**
 * @file
 * Contains \Drupal\support_ticket\Plugin\views\row\SupportTicketRow.
 */

namespace Drupal\support_ticket\Plugin\views\row;

use Drupal\views\Plugin\views\row\EntityRow;

/**
 * Plugin which performs a support_ticket_view on the resulting object.
 *
 * Most of the code on this object is in the theme function.
 *
 * @ingroup views_row_plugins
 *
 * @ViewsRow(
 *   id = "entity:support_ticket",
 * )
 */
class SupportTicketRow extends EntityRow {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['view_mode']['default'] = 'teaser';

    return $options;
  }

}
