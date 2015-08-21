<?php

/**
 * @file
 * Contains \Drupal\support_ticket\Plugin\views\argument\Type.
 */

namespace Drupal\support_ticket\Plugin\views\argument;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\views\Plugin\views\argument\StringArgument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler to accept a support ticket type.
 *
 * @ViewsArgument("support_ticket_type")
 */
class Type extends StringArgument {

  /**
   * SupportTicketType storage controller.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $supportTicketTypeStorage;

  /**
   * Constructs a new Support Ticket Type object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $support_ticket_type_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->supportTicketTypeStorage = $support_ticket_type_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $entity_manager = $container->get('entity.manager');
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $entity_manager->getStorage('support_ticket_type')
    );
  }

  /**
   * Override the behavior of summaryName(). Get the user friendly version
   * of the support ticket type.
   */
  public function summaryName($data) {
    return $this->support_ticket_type($data->{$this->name_alias});
  }

  /**
   * Override the behavior of title(). Get the user friendly version of the
   * support ticket type.
   */
  function title() {
    return $this->support_ticket_type($this->argument);
  }

  function support_ticket_type($type_name) {
    $type = $this->supportTicketTypeStorage->load($type_name);
    $output = $type ? $type->label() : $this->t('Unknown support ticket type');
    return SafeMarkup::checkPlain($output);
  }

}
