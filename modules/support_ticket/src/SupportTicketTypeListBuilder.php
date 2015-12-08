<?php

/**
 * @file
 * Contains \Drupal\support_ticket\SupportTicketTypeListBuilder.
 */

namespace Drupal\support_ticket;

use Drupal\content_entity_base\Entity\Listing\EntityTypeBaseListBuilder;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines a class to build a listing of support ticket type entities.
 *
 * @see \Drupal\support_ticket\Entity\SupportTicketType
 */
class SupportTicketTypeListBuilder extends EntityTypeBaseListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['title'] = t('Name');
    $header['description'] = array(
      'data' => t('Description'),
      'class' => array(RESPONSIVE_PRIORITY_MEDIUM),
    );
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['title'] = array(
      'data' => $this->getLabel($entity),
      'class' => array('menu-label'),
    );
    $row['description']['data'] = ['#markup' => $entity->getDescription()];
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('No support ticket types available. <a href="@link">Add support ticket type</a>.', [
        '@link' => Url::fromRoute('support_ticket.type_add')->toString()
      ]);
    return $build;
  }

}
