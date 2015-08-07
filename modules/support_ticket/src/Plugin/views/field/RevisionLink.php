<?php

/**
 * @file
 * Contains \Drupal\support_ticket\Plugin\views\field\RevisionLink.
 */

namespace Drupal\support_ticket\Plugin\views\field;

use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\LinkBase;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to a support ticket revision.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("support_ticket_revision_link")
 */
class RevisionLink extends LinkBase {

  /**
   * {@inheritdoc}
   */
  protected function getUrlInfo(ResultRow $row) {
    /** @var \Drupal\support_ticket\SupportTicketInterface $support_ticket */
    $support_ticket = $this->getEntity($row);
    // Current revision uses the support ticket view path.
    return !$support_ticket->isDefaultRevision() ?
      Url::fromRoute('entity.support_ticket.revision', ['support_ticket' => $support_ticket->id(), 'support_ticket_revision' => $support_ticket->getRevisionId()]) :
      $support_ticket->urlInfo();
  }

  /**
   * {@inheritdoc}
   */
  protected function renderLink(ResultRow $row) {
    /** @var \Drupal\support_ticket\SupportTicketInterface $support_ticket */
    $support_ticket = $this->getEntity($row);
    if (!$support_ticket->getRevisionId()) {
      return '';
    }
    $text = parent::renderLink($row);
    $this->options['alter']['query'] = $this->getDestinationArray();
    return $text;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('View');
  }

}
