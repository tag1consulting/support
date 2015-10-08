<?php

/**
 * @file
 * Contains \Drupal\support_ticket\Plugin\views\row\Rss.
 */

namespace Drupal\support_ticket\Plugin\views\row;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\views\Plugin\views\row\RssPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\support_ticket\SupportTicketStorageInterface;

/**
 * Plugin which performs a support_ticket_view on the resulting object
 * and formats it as an RSS item.
 *
 * @ViewsRow(
 *   id = "support_ticket_rss",
 *   title = @Translation("Tickets"),
 *   help = @Translation("Display the tickets with standard support ticket view."),
 *   theme = "views_view_row_rss",
 *   register_theme = FALSE,
 *   base = {"support_ticket_field_data"},
 *   display_types = {"feed"}
 * )
 */
class Rss extends RssPluginBase {

  // Basic properties that let the row style follow relationships.
  var $base_table = 'support_ticket_field_data';

  var $base_field = 'stid';

  // Stores the support tickets loaded with preRender.
  var $support_tickets = array();

  /**
   * {@inheritdoc}
   */
  protected $entityTypeId = 'support_ticket';

  /**
   * The support ticket storage
   *
   * @var \Drupal\support_ticket\SupportTicketStorageInterface
   */
  protected $supportTicketStorage;

  /**
   * Constructs the Rss object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_manager);
    $this->supportTicketStorage = $entity_manager->getStorage('support_ticket');
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm_summary_options() {
    $options = parent::buildOptionsForm_summary_options();
    $options['title'] = $this->t('Title only');
    $options['default'] = $this->t('Use site default RSS settings');
    return $options;
  }

  public function summaryTitle() {
    $options = $this->buildOptionsForm_summary_options();
    return SafeMarkup::checkPlain($options[$this->options['view_mode']]);
  }

  public function preRender($values) {
    $stids = array();
    foreach ($values as $row) {
      $stids[] = $row->{$this->field_alias};
    }
    if (!empty($stids)) {
      $this->support_tickets = $this->supportTicketStorage->loadMultiple($stids);
    }
  }

  public function render($row) {
    global $base_url;

    $stid = $row->{$this->field_alias};
    if (!is_numeric($stid)) {
      return;
    }

    $display_mode = $this->options['view_mode'];
    if ($display_mode == 'default') {
      $display_mode = \Drupal::config('system.rss')->get('items.view_mode');
    }

    // Load the specified support ticket:
    /** @var \Drupal\support_ticket\SupportTicketInterface $support_ticket */
    $support_ticket = $this->support_tickets[$stid];
    if (empty($support_ticket)) {
      return;
    }

    $description_build = [];

    $support_ticket->link = $support_ticket->url('canonical', array('absolute' => TRUE));
    $support_ticket->rss_namespaces = array();
    $support_ticket->rss_elements = array(
      array(
        'key' => 'pubDate',
        'value' => gmdate('r', $support_ticket->getCreatedTime()),
      ),
      array(
        'key' => 'dc:creator',
        'value' => $support_ticket->getOwner()->getDisplayName(),
      ),
      array(
        'key' => 'guid',
        'value' => $support_ticket->id() . ' at ' . $base_url,
        'attributes' => array('isPermaLink' => 'false'),
      ),
    );

    // The support ticket gets built and modules add to or modify $support_ticket->rss_elements
    // and $support_ticket->rss_namespaces.

    $build_mode = $display_mode;

    $build = support_ticket_view($support_ticket, $build_mode);
    unset($build['#theme']);

    if (!empty($support_ticket->rss_namespaces)) {
      $this->view->style_plugin->namespaces = array_merge($this->view->style_plugin->namespaces, $support_ticket->rss_namespaces);
    }
    elseif (function_exists('rdf_get_namespaces')) {
      // Merge RDF namespaces in the XML namespaces in case they are used
      // further in the RSS content.
      $xml_rdf_namespaces = array();
      foreach (rdf_get_namespaces() as $prefix => $uri) {
        $xml_rdf_namespaces['xmlns:' . $prefix] = $uri;
      }
      $this->view->style_plugin->namespaces += $xml_rdf_namespaces;
    }

    if ($display_mode != 'title') {
      // We render support ticket contents.
      $description_build = $build;
    }

    $item = new \stdClass();
    $item->description = $description_build;
    $item->title = $support_ticket->label();
    $item->link = $support_ticket->link;
    // Provide a reference so that the render call in
    // template_preprocess_views_view_row_rss() can still access it.
    $item->elements = &$support_ticket->rss_elements;
    $item->stid = $support_ticket->id();
    $build = array(
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#options' => $this->options,
      '#row' => $item,
    );

    return $build;
  }

}
