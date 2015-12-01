<?php

/**
 * @file
 * Contains \Drupal\support_ticket\Tests\SupportTicketViewTest.
 */

namespace Drupal\support_ticket\Tests;

/**
 * Tests the support_ticket/{support_ticket} page.
 *
 * @group support
 * @see \Drupal\support_ticket\Controller\SupportTicketController
 */
class SupportTicketViewTest extends SupportTicketTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('support_ticket_test');

  protected function setUp() {
    parent::setUp();

    $web_user = $this->drupalCreateUser(array('access support tickets'));
    $this->drupalLogin($web_user);
  }

  /**
   * Tests the html head links.
   */
  public function testHtmlHeadLinks() {
    $support_ticket = $this->drupalCreateSupportTicket();

    $this->drupalGet($support_ticket->urlInfo());

    $result = $this->xpath('//link[@rel = "version-history"]');
    $this->assertEqual($result[0]['href'], $support_ticket->url('version-history'));

    $result = $this->xpath('//link[@rel = "edit-form"]');
    $this->assertEqual($result[0]['href'], $support_ticket->url('edit-form'));

    $result = $this->xpath('//link[@rel = "canonical"]');
    $this->assertEqual($result[0]['href'], $support_ticket->url());
  }

  /**
   * Tests that we store and retrieve multi-byte UTF-8 characters correctly.
   */
  public function testMultiByteUtf8() {
    $title = '🐝';
    $this->assertTrue(mb_strlen($title, 'utf-8') < strlen($title), 'Title has multi-byte characters.');
    $support_ticket = $this->drupalCreateSupportTicket(array('title' => $title));
    $this->drupalGet($support_ticket->urlInfo());
    $result = $this->xpath('//span[contains(@class, "field--name-title")]');
    $this->assertEqual((string) $result[0], $title, 'The passed title was returned.');
  }

}
