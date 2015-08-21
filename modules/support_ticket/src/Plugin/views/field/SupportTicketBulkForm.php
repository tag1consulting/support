<?php

/**
 * @file
 * Contains \Drupal\support_ticket\Plugin\views\field\SupportTicketBulkForm.
 */

namespace Drupal\support_ticket\Plugin\views\field;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\system\Plugin\views\field\BulkForm;

/**
 * Defines a support ticket operations bulk form element.
 *
 * @ViewsField("support_ticket_bulk_form")
 */
class SupportTicketBulkForm extends BulkForm {

  /**
   * {@inheritdoc}
   */
  protected function emptySelectedMessage() {
    return $this->t('No tickets selected.');
  }

}
