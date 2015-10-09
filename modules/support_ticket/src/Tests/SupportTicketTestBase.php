<?php

/**
 * @file
 * Contains \Drupal\support_ticket\Tests\SupportTicketTestBase.
 */

namespace Drupal\support_ticket\Tests;

use Drupal\Core\Session\AccountInterface;
use Drupal\simpletest\WebTestBase;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\support_ticket\Entity\SupportTicketType;

/**
 * Sets up ticket type.
 */
abstract class SupportTicketTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('support_ticket');

  /**
   * The support ticket access control handler.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $accessHandler;

  /**
   * Creates a custom ticket type based on default settings.
   *
   * @param array $values
   *   An array of settings to change from the defaults.
   *   Example: 'type' => 'foo'.
   *
   * @return \Drupal\support_ticket\Entity\SupportTicketType
   *   Created support ticket type.
   */
  protected function supportTicketCreateSupportTicketType(array $values = array()) {
    // Find a non-existent random type name.
    if (!isset($values['type'])) {
      do {
        $id = strtolower($this->randomMachineName(8));
      } while (SupportTicketType::load($id));
    }
    else {
      $id = $values['type'];
    }
    $values += array(
      'type' => $id,
      'name' => $id,
    );
    $type = entity_create('support_ticket_type', $values);
    $status = $type->save();
    support_ticket_add_body_field($type);
    \Drupal::service('router.builder')->rebuild();

    $this->assertEqual($status, SAVED_NEW, SafeMarkup::format('Created support ticket type %type.', array('%type' => $type->id())));

    return $type;
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create ticket support ticket type.
    // DISABLED -- it should get set up during installation automatically.
    //$this->supportTicketCreateSupportTicketType(array('type' => 'ticket', 'name' => 'Ticket'));
    $this->accessHandler = \Drupal::entityManager()->getAccessControlHandler('support_ticket');
  }

  /**
   * Asserts that support ticket access correctly grants or denies access.
   *
   * @param array $ops
   *   An associative array of the expected support ticket access grants for the
   *   support ticket and account, with each key as the name of an operation (e.g.
   *   'view', 'delete') and each value a Boolean indicating whether access to that
   *   operation should be granted.
   * @param \Drupal\support_ticket\Entity\SupportTicket $support_ticket
   *   The support ticket object to check.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account for which to check access.
   */
  function assertSupportTicketAccess(array $ops, $support_ticket, AccountInterface $account) {
    foreach ($ops as $op => $result) {
      $this->assertEqual($result, $this->accessHandler->access($support_ticket, $op, $account), $this->supportTicketAccessAssertMessage($op, $result, $support_ticket->language()->getId()));
    }
  }

  /**
   * Asserts that support ticket create access correctly grants or denies access.
   *
   * @param string $bundle
   *   The support ticket bundle to check access to.
   * @param bool $result
   *   Whether access should be granted or not.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account for which to check access.
   * @param string|null $langcode
   *   (optional) The language code indicating which translation of the support ticket
   *   to check. If NULL, the untranslated (fallback) access is checked.
   */
  function assertSupportTicketCreateAccess($bundle, $result, AccountInterface $account, $langcode = NULL) {
    $this->assertEqual($result, $this->accessHandler->createAccess($bundle, $account, array(
      'langcode' => $langcode,
    )), $this->supportTicketAccessAssertMessage('create', $result, $langcode));
  }

  /**
   * Constructs an assert message to display which support ticket access was tested.
   *
   * @param string $operation
   *   The operation to check access for.
   * @param bool $result
   *   Whether access should be granted or not.
   * @param string|null $langcode
   *   (optional) The language code indicating which translation of the support ticket
   *   to check. If NULL, the untranslated (fallback) access is checked.
   *
   * @return string
   *   An assert message string which contains information in plain English
   *   about the support ticket access permission test that was performed.
   */
  function supportTicketAccessAssertMessage($operation, $result, $langcode = NULL) {
    return format_string(
      'Support ticket access returns @result with operation %op, language code %langcode.',
      array(
        '@result' => $result ? 'true' : 'false',
        '%op' => $operation,
        '%langcode' => !empty($langcode) ? $langcode : 'empty'
      )
    );
  }

  /**
   * Get a support ticket from the database based on its title.
   *
   * @param $title
   *   A ticket title, usually generated by $this->randomMachineName().
   * @param $reset
   *   (optional) Whether to reset the entity cache.
   *
   * @return \Drupal\support_ticket\SupportTicketInterface
   *   A support_ticket entity matching $title.
   */
  function supportTicketGetTicketByTitle($title, $reset = FALSE) {
    if ($reset) {
      \Drupal::entityManager()->getStorage('support_ticket')->resetCache();
    }
    $tickets = entity_load_multiple_by_properties('support_ticket', array('title' => $title));
    // Load the first ticket returned from the database.
    $returned_ticket = reset($tickets);
    return $returned_ticket;
  }

  /**
   * Creates a support_ticket based on default settings.
   *
   * @param array $settings
   *   (optional) An associative array of settings for the support_ticket, as used in
   *   entity_create(). Override the defaults by specifying the key and value
   *   in the array, for example:
   *   @code
   *     $this->drupalCreateNode(array(
   *       'title' => t('Hello, world!'),
   *       'type' => 'article',
   *     ));
   *   @endcode
   *   The following defaults are provided:
   *   - body: Random string using the default filter format:
   *     @code
   *       $settings['body'][0] = array(
   *         'value' => $this->randomMachineName(32),
   *         'format' => filter_default_format(),
   *       );
   *     @endcode
   *   - title: Random string.
   *   - type: 'page'.
   *   - uid: The currently logged in user, or anonymous.
   *
   * @return \Drupal\support_ticket\Entity\SupportTicket
   *   The created support ticket.
   */
  protected function drupalCreateSupportTicket(array $settings = array()) {
    // Populate defaults array.
    $settings += array(
      'body'      => array(array(
        'value' => $this->randomMachineName(32),
        'format' => filter_default_format(),
      )),
      'title'     => $this->randomMachineName(8),
      'support_ticket_type'      => 'ticket',
      'uid'       => \Drupal::currentUser()->id(),
    );
    $support_ticket = entity_create('support_ticket', $settings);
    $support_ticket->save();

    return $support_ticket;
  }
}
