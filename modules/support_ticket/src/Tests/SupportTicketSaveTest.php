<?php

/**
 * @file
 * Contains \Drupal\support_ticket\Tests\SupportTicketSaveTest.
 */

namespace Drupal\support_ticket\Tests;

use Drupal\support_ticket\Entity\SupportTicket;

/**
 * Tests $support_ticket->save() for saving tickets.
 *
 * @group support
 */
class SupportTicketSaveTest extends SupportTicketTestBase {

  /**
   * A normal logged in user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('support_ticket_test');

  protected function setUp() {
    parent::setUp();

    // Create a user that is allowed to post; we'll use this to test the submission.
    $web_user = $this->drupalCreateUser(array('access support tickets', 'create ticket ticket'));
    $this->drupalLogin($web_user);
    $this->webUser = $web_user;
  }

  /**
   * Checks whether custom support_ticket IDs are saved properly during an import operation.
   *
   * Workflow:
   *  - first create a piece of ticket
   *  - save the ticket
   *  - check if support_ticket exists
   */
  function testImport() {
    // SupportTicket ID must be a number that is not in the database.
    $stids = \Drupal::entityManager()->getStorage('support_ticket')->getQuery()
      ->sort('stid', 'DESC')
      ->range(0, 1)
      ->execute();
    $max_stid = reset($stids);
    $test_stid = $max_stid + mt_rand(1000, 1000000);
    $title = $this->randomMachineName(8);
    $support_ticket = array(
      'title' => $title,
      'body' => array(array('value' => $this->randomMachineName(32))),
      'uid' => $this->webUser->id(),
      'support_ticket_type' => 'ticket',
      'stid' => $test_stid,
    );
    /** @var \Drupal\support_ticket\SupportTicketInterface $support_ticket */
    $support_ticket = entity_create('support_ticket', $support_ticket);
    $support_ticket->enforceIsNew();

    $this->assertEqual($support_ticket->getOwnerId(), $this->webUser->id());

    $support_ticket->save();
    // Test the import.
    $support_ticket_by_stid = SupportTicket::load($test_stid);
    $this->assertTrue($support_ticket_by_stid, 'SupportTicket load by support_ticket ID.');

    $support_ticket_by_title = $this->supportTicketGetTicketByTitle($title);
    $this->assertTrue($support_ticket_by_title, 'SupportTicket load by support_ticket title.');
  }

  /**
   * Verifies accuracy of the "created" and "changed" timestamp functionality.
   */
  function testTimestamps() {
    // Use the default timestamps.
    $edit = array(
      'uid' => $this->webUser->id(),
      'support_ticket_type' => 'ticket',
      'title' => $this->randomMachineName(8),
    );

    entity_create('support_ticket', $edit)->save();
    $support_ticket = $this->supportTicketGetTicketByTitle($edit['title']);
    $this->assertEqual($support_ticket->getCreatedTime(), REQUEST_TIME, 'Creating a support_ticket sets default "created" timestamp.');
    $this->assertEqual($support_ticket->getChangedTime(), REQUEST_TIME, 'Creating a support_ticket sets default "changed" timestamp.');

    // Store the timestamps.
    $created = $support_ticket->getCreatedTime();

    $support_ticket->save();
    $support_ticket = $this->supportTicketGetTicketByTitle($edit['title'], TRUE);
    $this->assertEqual($support_ticket->getCreatedTime(), $created, 'Updating a support_ticket preserves "created" timestamp.');

    // Programmatically set the timestamps using hook_ENTITY_TYPE_presave().
    $support_ticket->title = 'testing_support_ticket_presave';

    $support_ticket->save();
    $support_ticket = $this->supportTicketGetTicketByTitle('testing_support_ticket_presave', TRUE);
    $this->assertEqual($support_ticket->getCreatedTime(), 280299600, 'Saving a support_ticket uses "created" timestamp set in presave hook.');
    $this->assertEqual($support_ticket->getChangedTime(), 979534800, 'Saving a support_ticket uses "changed" timestamp set in presave hook.');

    // Programmatically set the timestamps on the support_ticket.
    $edit = array(
      'uid' => $this->webUser->id(),
      'support_ticket_type' => 'ticket',
      'title' => $this->randomMachineName(8),
      'created' => 280299600, // Sun, 19 Nov 1978 05:00:00 GMT
      'changed' => 979534800, // Drupal 1.0 release.
    );

    entity_create('support_ticket', $edit)->save();
    $support_ticket = $this->supportTicketGetTicketByTitle($edit['title']);
    $this->assertEqual($support_ticket->getCreatedTime(), 280299600, 'Creating a support_ticket programmatically uses programmatically set "created" timestamp.');
    $this->assertEqual($support_ticket->getChangedTime(), 979534800, 'Creating a support_ticket programmatically uses programmatically set "changed" timestamp.');

    // Update the timestamps.
    $support_ticket->setCreatedTime(979534800);
    $support_ticket->changed = 280299600;

    $support_ticket->save();
    $support_ticket = $this->supportTicketGetTicketByTitle($edit['title'], TRUE);
    $this->assertEqual($support_ticket->getCreatedTime(), 979534800, 'Updating a support_ticket uses user-set "created" timestamp.');
    // Allowing setting changed timestamps is required, see
    // Drupal\ticket_translation\ContentTranslationMetadataWrapper::setChangedTime($timestamp)
    // for example.
    $this->assertEqual($support_ticket->getChangedTime(), 280299600, 'Updating a support_ticket uses user-set "changed" timestamp.');
  }

  /**
   * Tests support_ticket presave and static support_ticket load cache.
   *
   * This test determines changes in hook_ENTITY_TYPE_presave() and verifies
   * that the static support_ticket load cache is cleared upon save.
   */
  function testDeterminingChanges() {
    // Initial creation.
    $support_ticket = entity_create('support_ticket', array(
      'uid' => $this->webUser->id(),
      'support_ticket_type' => 'ticket',
      'title' => 'test_changes',
    ));
    $support_ticket->save();

    // Update the support_ticket without applying changes.
    $support_ticket->save();
    $this->assertEqual($support_ticket->label(), 'test_changes', 'No changes have been determined.');

    // Apply changes.
    $support_ticket->title = 'updated';
    $support_ticket->save();

    // The hook implementations support_ticket_test_support_ticket_presave() and
    // support_ticket_test_support_ticket_update() determine changes and change the title.
    $this->assertEqual($support_ticket->label(), 'updated_presave_update', 'Changes have been determined.');

    // Test the static support_ticket load cache to be cleared.
    $support_ticket = SupportTicket::load($support_ticket->id());
    $this->assertEqual($support_ticket->label(), 'updated_presave', 'Static cache has been cleared.');
  }

  /**
   * Tests saving a support_ticket on support_ticket insert.
   *
   * This test ensures that a support_ticket has been fully saved when
   * hook_ENTITY_TYPE_insert() is invoked, so that the support_ticket can be saved again
   * in a hook implementation without errors.
   *
   * @see support_ticket_test_support_ticket_insert()
   */
  /*
   * Disable this test for now -- there's something odd going on in comment.
  function testSupportTicketSaveOnInsert() {
    // support_ticket_test_support_ticket_insert() triggers a save on insert if the title equals
    // 'new'.
    $support_ticket = $this->drupalCreateSupportTicket(array('title' => 'new'));
    $this->assertEqual($support_ticket->getTitle(), 'Support ticket ' . $support_ticket->id(), 'Support ticket saved on support_ticket insert.');
  }
  */
}
