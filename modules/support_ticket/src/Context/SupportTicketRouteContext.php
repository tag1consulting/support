<?php

/**
 * @file
 * Contains \Drupal\support\ContextProvider\SupportTicketRouteContext.
 */

namespace Drupal\support\ContextProvider;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\support\Entity\SupportTicket;

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
    $context = new Context(new ContextDefinition('entity:support_ticket', NULL, FALSE));
    if (($route_object = $this->routeMatch->getRouteObject()) && ($route_contexts = $route_object->getOption('parameters')) && isset($route_contexts['support_ticket'])) {
      if ($suppor_ticket = $this->routeMatch->getParameter('support_ticket')) {
        $context->setContextValue($support_ticket);
      }
    }
    elseif ($this->routeMatch->getRouteName() == 'support_ticket.add') {
      $support_ticket_type = $this->routeMatch->getParameter('support_ticket_type');
      $context->setContextValue(SupportTicket::create(array('type' => $support_ticket_type->id())));
    }
    $cacheability = new CacheableMetadata();
    $cacheability->setCacheContexts(['route']);
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
