<?php

/**
 * @file
 * Contains \Drupal\support_ticket\Tests\SupportTicketTitleXSSTest.
 */

namespace Drupal\support_ticket\Tests;

use Drupal\Component\Utility\Html;

/**
 * Create a support_ticket with dangerous tags in its title and test that they are
 * escaped.
 *
 * @group support
 */
class SupportTicketTitleXSSTest extends SupportTicketTestBase {
  /**
   * Tests XSS functionality with a support_ticket entity.
   */
  function testSupportTicketTitleXSS() {
    // Prepare a user to do the stuff.
    $web_user = $this->drupalCreateUser(array('access support tickets', 'create ticket ticket', 'edit any ticket ticket'));
    $this->drupalLogin($web_user);

    $xss = '<script>alert("xss")</script>';
    $title = $xss . $this->randomMachineName();
    $edit = array();
    $edit['title[0][value]'] = $title;

    $this->drupalPostForm('support_ticket/add/ticket', $edit, t('Preview'));
    $this->assertNoRaw($xss, 'Harmful tags are escaped when previewing a support_ticket.');

    $settings = array('title' => $title);
    $support_ticket = $this->drupalCreateSupportTicket($settings);

    $this->drupalGet('support_ticket/' . $support_ticket->id());
    // Titles should be escaped.
    $this->assertTitle(Html::escape($title) . ' | Drupal', 'Title is displayed when viewing a support_ticket.');
    $this->assertNoRaw($xss, 'Harmful tags are escaped when viewing a support_ticket.');

    $this->drupalGet('support_ticket/' . $support_ticket->id() . '/edit');
    $this->assertNoRaw($xss, 'Harmful tags are escaped when editing a support_ticket.');
  }
}
