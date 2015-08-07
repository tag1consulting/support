<?php

/**
 * @file
 * Contains \Drupal\support_ticket\Plugin\views\argument_default\SupportTicket.
 */

namespace Drupal\support_ticket\Plugin\views\argument_default;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\views\Plugin\CacheablePluginInterface;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;
use Drupal\support_ticket\SupportTicketInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Default argument plugin to extract a support ticket.
 *
 * This plugin actually has no options so it does not need to do a great deal.
 *
 * @ViewsArgumentDefault(
 *   id = "support_ticket",
 *   title = @Translation("Ticket ID from URL")
 * )
 */
class SupportTicket extends ArgumentDefaultPluginBase implements CacheablePluginInterface {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new SupportTicket instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument() {
    if (($support_ticket = $this->routeMatch->getParameter('support_ticket')) && $support_ticket instanceof SupportTicketInterface) {
      return $support_ticket->id();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isCacheable() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['url'];
  }

}
