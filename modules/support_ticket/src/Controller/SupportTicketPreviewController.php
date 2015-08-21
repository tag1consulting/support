<?php

/**
 * @file
 * Contains \Drupal\support_ticket\Controller\SupportTicketPreviewController.
 */

namespace Drupal\support_ticket\Controller;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Controller\EntityViewController;

/**
 * Defines a controller to render a single support ticket in preview.
 */
class SupportTicketPreviewController extends EntityViewController {

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $support_ticket_preview, $view_mode_id = 'full', $langcode = NULL) {
    $support_ticket_preview->preview_view_mode = $view_mode_id;
    $build = parent::view($support_ticket_preview, $view_mode_id);

    $build['#attached']['library'][] = 'support_ticket/drupal.support_ticket.preview';

    // Don't render cache previews.
    unset($build['#cache']);

    foreach ($support_ticket_preview->uriRelationships() as $rel) {
      // Set the support ticket path as the canonical URL to prevent duplicate tickets.
      $build['#attached']['html_head_link'][] = array(
        array(
        'rel' => $rel,
        'href' => $support_ticket_preview->url($rel),
        )
        , TRUE);

      if ($rel == 'canonical') {
        // Set the non-aliased canonical path as a default shortlink.
        $build['#attached']['html_head_link'][] = array(
          array(
            'rel' => 'shortlink',
            'href' => $support_ticket_preview->url($rel, array('alias' => TRUE)),
          )
        , TRUE);
      }
    }

    return $build;
  }

  /**
   * The _title_callback for the page that renders a single support ticket in preview.
   *
   * @param \Drupal\Core\Entity\EntityInterface $support_ticket_preview
   *   The current support ticket.
   *
   * @return string
   *   The page title.
   */
  public function title(EntityInterface $support_ticket_preview) {
    return SafeMarkup::checkPlain($this->entityManager->getTranslationFromContext($support_ticket_preview)->label());
  }

}
