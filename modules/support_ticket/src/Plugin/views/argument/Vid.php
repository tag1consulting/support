<?php

/**
 * @file
 * Contains \Drupal\support_ticket\Plugin\views\argument\Vid.
 */

namespace Drupal\support_ticket\Plugin\views\argument;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Database\Connection;
use Drupal\views\Plugin\views\argument\NumericArgument;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\support_ticket\SupportTicketStorageInterface;

/**
 * Argument handler to accept a support ticket revision id.
 *
 * @ViewsArgument("support_ticket_vid")
 */
class Vid extends NumericArgument {

  /**
   * Database Service Object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The support ticket storage.
   *
   * @var \Drupal\support_ticket\SupportTicketStorageInterface
   */
  protected $supportTicketStorage;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $database
   *   Database Service Object.
   * @param \Drupal\support_ticket\SupportTicketStorageInterface
   *   The support ticket storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database, SupportTicketStorageInterface $support_ticket_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->database = $database;
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
      $container->get('database'),
      $container->get('entity.manager')->getStorage('support_ticket')
    );
  }

  /**
   * Override the behavior of title(). Get the title of the revision.
   */
  public function titleQuery() {
    $titles = array();

    $results = $this->database->query('SELECT nr.vid, nr.stid, npr.title FROM {support_ticket_revision} nr WHERE nr.vid IN ( :vids[] )', array(':vids[]' => $this->value))->fetchAllAssoc('vid', PDO::FETCH_ASSOC);
    $stids = array();
    foreach ($results as $result) {
      $stids[] = $result['stid'];
    }

    $support_tickets = $this->supportTicketStorage->loadMultiple(array_unique($stids));

    foreach ($results as $result) {
      $support_tickets[$result['stid']]->set('title', $result['title']);
      $titles[] = SafeMarkup::checkPlain($support_tickets[$result['stid']]->label());
    }

    return $titles;
  }

}
