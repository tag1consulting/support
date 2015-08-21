<?php

/**
 * @file
 * Contains \Drupal\support_ticket\Plugin\views\argument\Stid.
 */

namespace Drupal\support_ticket\Plugin\views\argument;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\support_ticket\SupportTicketStorageInterface;
use Drupal\views\Plugin\views\argument\NumericArgument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler to accept a support ticket id.
 *
 * @ViewsArgument("support_ticket_stid")
 */
class Stid extends NumericArgument {

  /**
   * The support ticket storage.
   *
   * @var \Drupal\support_ticket\SupportTicketStorageInterface
   */
  protected $supportTicketStorage;

  /**
   * Constructs the Stid object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param SupportTicketStorageInterface $support_ticket_storage
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SupportTicketStorageInterface $support_ticket_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->supportTicketStorage = $support_ticket_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')->getStorage('support_ticket')
    );
  }

  /**
   * Override the behavior of title(). Get the title of the support ticket.
   */
  public function titleQuery() {
    $titles = array();

    $support_tickets = $this->supportTicketStorage->loadMultiple($this->value);
    foreach ($support_tickets as $support_ticket) {
      $titles[] = SafeMarkup::checkPlain($support_ticket->label());
    }
    return $titles;
  }

}
