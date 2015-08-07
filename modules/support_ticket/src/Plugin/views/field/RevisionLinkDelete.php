<?php

/**
 * @file
 * Contains \Drupal\support_ticket\Plugin\views\field\RevisionLinkDelete.
 */

namespace Drupal\support_ticket\Plugin\views\field;

use Drupal\Core\Url;
use Drupal\views\ResultRow;

/**
 * Field handler to present link to delete a support ticket revision.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("support_ticket_revision_link_delete")
 */
class RevisionLinkDelete extends RevisionLink {

  /**
   * {@inheritdoc}
   */
  protected function getUrlInfo(ResultRow $row) {
    /** @var \Drupal\support_ticket\SupportTicketInterface $support_ticket */
    $support_ticket = $this->getEntity($row);
    return Url::fromRoute('support_ticket.revision_delete_confirm', ['support_ticket' => $support_ticket->id(), 'support_ticket_revision' => $support_ticket->getRevisionId()]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('Delete');
  }

}
