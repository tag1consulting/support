<?php

/**
 * @file
 * Contains \Drupal\support_ticket\Entity\SupportTicketRouteProvider.
 */

namespace Drupal\support_ticket\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\EntityRouteProviderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides routes for support tickets.
 */
class SupportTicketRouteProvider implements EntityRouteProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getRoutes( EntityTypeInterface $entity_type) {
    $route_collection = new RouteCollection();
    $route = (new Route('/support_ticket/{support_ticket}'))
      ->addDefaults([
        '_controller' => '\Drupal\support_ticket\Controller\SupportTicketViewController::view',
        '_title_callback' => '\Drupal\support_ticket\Controller\SupportTicketViewController::title',
      ])
      ->setRequirement('_entity_access', 'support_ticket.view');
    $route_collection->add('entity.support_ticket.canonical', $route);

    $route = (new Route('/support_ticket/{support_ticket}/delete'))
      ->addDefaults([
        '_entity_form' => 'support_ticket.delete',
        '_title' => 'Delete',
      ])
      ->setRequirement('_entity_access', 'support_ticket.delete')
      ->setOption('_support_ticket_operation_route', TRUE);
    $route_collection->add('entity.support_ticket.delete_form', $route);

    $route = (new Route('/support_ticket/{support_ticket}/edit'))
      ->setDefault('_entity_form', 'support_ticket.edit')
      ->setRequirement('_entity_access', 'support_ticket.update')
      ->setOption('_support_ticket_operation_route', TRUE);
    $route_collection->add('entity.support_ticket.edit_form', $route);

    return $route_collection;
  }

}
