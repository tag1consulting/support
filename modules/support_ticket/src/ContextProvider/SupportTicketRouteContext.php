<?php

/**
 * @file
 * Contains \Drupal\support_ticket\ContextProvider\SupportTicketRouteContext.
 */

namespace Drupal\support_ticket\ContextProvider;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\support_ticket\Entity\SupportTicket;

/**
 * Sets the current support ticket as a context on support ticket routes.
 */
class SupportTicketRouteContext implements ContextProviderInterface {

  /**
   * The route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new SupportTicketRouteContext.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    $result = [];
    $context_definition = new ContextDefinition('entity:support_ticket', NULL, FALSE);
    $value = NULL;
    if (($route_object = $this->routeMatch->getRouteObject()) && ($route_contexts = $route_object->getOption('parameters')) && isset($route_contexts['support_ticket'])) {
      if ($support_ticket = $this->routeMatch->getParameter('support_ticket')) {
        $value = $support_ticket;
      }
    }
    elseif ($this->routeMatch->getRouteName() == 'support_ticket.add') {
      $support_ticket_type = $this->routeMatch->getParameter('support_ticket_type');
      $value = SupportTicket::create(array('type' => $support_ticket_type->id()));
    }
    $cacheability = new CacheableMetadata();
    $cacheability->setCacheContexts(['route']);
    $context = new Context($context_definition, $value);
    $context->addCacheableDependency($cacheability);
    $result['support_ticket'] = $context;

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    $context = new Context(new ContextDefinition('entity:support_ticket'));
    return ['support_ticket' => $context];
  }

}
