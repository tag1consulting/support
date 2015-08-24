<?php

/**
 * @file
 * Contains \Drupal\support_ticket\Plugin\views\field\RevisionLinkRevert.
 */

namespace Drupal\support_ticket\Plugin\views\field;

use Drupal\Core\Url;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to revert a support ticket to a revision.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("support_ticket_revision_link_revert")
 */
class RevisionLinkRevert extends RevisionLink {

  /**
   * {@inheritdoc}
   */
  protected function getUrlInfo(ResultRow $row) {
    /** @var \Drupal\support_ticket\SupportTicketInterface $support_ticket */
    $support_ticket = $this->getEntity($row);
    return Url::fromRoute('support_ticket.revision_revert_confirm', ['support_ticket' => $support_ticket->id(), 'support_ticket_revision' => $support_ticket->getRevisionId()]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('Revert');
  }
}
