<?php

/**
 * @file
 * Contains \Drupal\support_ticket\Plugin\EntityReferenceSelection\SupportTicketSelection.
 */

namespace Drupal\support_ticket\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides specific access control for the support ticket entity type.
 *
 * @EntityReferenceSelection(
 *   id = "default:support_ticket",
 *   label = @Translation("Support ticket selection"),
 *   entity_types = {"support_ticket"},
 *   group = "default",
 *   weight = 1
 * )
 */
class SupportTicketSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['target_bundles']['#title'] = $this->t('Support ticket types');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);
    // Adding the 'support_ticket_access' tag is sadly insufficient for support
    // tickets: core requires us to also know about the concept of 'published' and
    // 'unpublished'.
    $query->condition('status', SUPPORT_TICKET_PUBLISHED);
    return $query;
  }

}
