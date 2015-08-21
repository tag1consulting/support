<?php

/**
 * @file
 * Contains \Drupal\support_ticket\Controller\SupportTicketViewController.
 */

namespace Drupal\support_ticket\Controller;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Controller\EntityViewController;

/**
 * Defines a controller to render a single support ticket.
 */
class SupportTicketViewController extends EntityViewController {

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $support_ticket, $view_mode = 'full', $langcode = NULL) {
    $build = parent::view($support_ticket);

    foreach ($support_ticket->uriRelationships() as $rel) {
      // Set the support ticket path as the canonical URL to prevent duplicate tickets.
      $build['#attached']['html_head_link'][] = array(
        array(
          'rel' => $rel,
          'href' => $support_ticket->url($rel),
        ),
        TRUE,
      );

      if ($rel == 'canonical') {
        // Set the non-aliased canonical path as a default shortlink.
        $build['#attached']['html_head_link'][] = array(
          array(
            'rel' => 'shortlink',
            'href' => $support_ticket->url($rel, array('alias' => TRUE)),
          ),
          TRUE,
        );
      }
    }

    return $build;
  }

  /**
   * The _title_callback for the page that renders a single support ticket.
   *
   * @param \Drupal\Core\Entity\EntityInterface $support_ticket
   *   The current support ticket.
   *
   * @return string
   *   The page title.
   */
  public function title(EntityInterface $support_ticket) {
    return $this->entityManager->getTranslationFromContext($support_ticket)->label();
  }

}
