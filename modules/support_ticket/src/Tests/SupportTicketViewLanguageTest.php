<?php

/**
 * @file
 * Contains \Drupal\support_ticket\Tests\SupportTicketViewLanguageTest.
 */

namespace Drupal\support_ticket\Tests;

use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests the support_ticket language extra field display.
 *
 * @group support
 */
class SupportTicketViewLanguageTest extends SupportTicketTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('support_ticket', 'datetime', 'language');

  protected function setUp() {
    parent::setUp();

    $web_user = $this->drupalCreateUser(array('access support tickets'));
    $this->drupalLogin($web_user);
  }

  /**
   * Tests the language extra field display.
   */
  public function testViewLanguage() {
    // Add Spanish language.
    ConfigurableLanguage::createFromLangcode('es')->save();

    // Set language field visible.
    entity_get_display('support_ticket', 'ticket', 'default')
      ->setComponent('langcode')
      ->save();

    // Create a support_ticket in Spanish.
    $support_ticket = $this->drupalCreateSupportTicket(array('langcode' => 'es'));

    $this->drupalGet($support_ticket->urlInfo());
    $this->assertText('Spanish','The language field is displayed properly.');
  }

}
