<?php

/**
 * @file
 * Contains \Drupal\support_ticket\SupportTicketViewBuilder.
 */

namespace Drupal\support_ticket;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\support_ticket\Entity\SupportTicket;
use Drupal\user\Entity\User;

/**
 * Render controller for support tickets.
 */
class SupportTicketViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    /** @var \Drupal\support_ticket\SupportTicketInterface[] $entities */
    if (empty($entities)) {
      return;
    }

    parent::buildComponents($build, $entities, $displays, $view_mode);

    foreach ($entities as $id => $entity) {
      $bundle = $entity->bundle();
      $display = $displays[$bundle];

      if ($display->getComponent('links')) {
        $build[$id]['links'] = array(
          '#lazy_builder' => [get_called_class() . '::renderLinks', [
            $entity->id(),
            $view_mode,
            $entity->language()->getId(),
          ]],
          '#create_placeholder' => TRUE,
        );
      }

      // Add Language field text element to support ticket render array.
      if ($display->getComponent('langcode')) {
        $build[$id]['langcode'] = array(
          '#type' => 'item',
          '#title' => t('Language'),
          '#markup' => $entity->language()->getName(),
          '#prefix' => '<div id="field-language-display">',
          '#suffix' => '</div>'
        );
      }
    }
  }

  /**
   * #lazy_builder callback; builds a support ticket's links.
   *
   * @param string $support_ticket_entity_id
   *   The support ticket entity ID.
   * @param string $view_mode
   *   The view mode in which the support ticket entity is being viewed.
   * @param string $langcode
   *   The language in which the support ticket entity is being viewed.
   *
   * @return array
   *   A renderable array representing the support ticket links.
   */
  public static function renderLinks($support_ticket_entity_id, $view_mode, $langcode) {
    $links = array(
      '#theme' => 'links__support_ticket',
      '#pre_render' => array('drupal_pre_render_links'),
      '#attributes' => array('class' => array('links', 'inline')),
    );

    $entity = SupportTicket::load($support_ticket_entity_id)->getTranslation($langcode);
    $links['support_ticket'] = static::buildLinks($entity, $view_mode);

    // Allow other modules to alter the support_ticket links.
    $hook_context = array(
      'view_mode' => $view_mode,
      'langcode' => $langcode,
    );
    \Drupal::moduleHandler()->alter('support_ticket_links', $links, $entity, $hook_context);

    return $links;
  }

  /**
   * Build the default links (Read more) for a support ticket.
   *
   * @param \Drupal\support_ticket\SupportTicketInterface $entity
   *   The support ticket object.
   * @param string $view_mode
   *   A view mode identifier.
   *
   * @return array
   *   An array that can be processed by drupal_pre_render_links().
   */
  protected static function buildLinks(SupportTicketInterface $entity, $view_mode) {
    $links = array();

    // Always display a read more link on teasers because we have no way
    // to know when a teaser view is different than a full view.
    if ($view_mode == 'teaser') {
      $support_ticket_title_stripped = strip_tags($entity->label());
      $links['support_ticket-readmore'] = array(
        'title' => t('Read more<span class="visually-hidden"> about @title</span>', array(
          '@title' => $support_ticket_title_stripped,
        )),
        'url' => $entity->urlInfo(),
        'language' => $entity->language(),
        'attributes' => array(
          'rel' => 'tag',
          'title' => $support_ticket_title_stripped,
        ),
      );
    }

    return array(
      '#theme' => 'links__support_ticket__support_ticket',
      '#links' => $links,
      '#attributes' => array('class' => array('links', 'inline')),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function alterBuild(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {
    /** @var \Drupal\support_ticket\SupportTicketInterface $entity */
    parent::alterBuild($build, $entity, $display, $view_mode);
    if ($entity->id()) {
      $build['#contextual_links']['support_ticket'] = array(
        'route_parameters' =>array('support_ticket' => $entity->id()),
        'metadata' => array('changed' => $entity->getChangedTime()),
      );
    }
  }

}
