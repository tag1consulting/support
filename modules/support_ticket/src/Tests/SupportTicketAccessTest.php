<?php

/**
 * @file
 * Contains \Drupal\support_ticket\Tests\SupportTicketAccessTest.
 */

namespace Drupal\support_ticket\Tests;

use Drupal\user\RoleInterface;

/**
 * Tests basic support_ticket_access functionality.
 *
 * Note that hook_support_ticket_access_records() is covered in another test class.
 *
 * @group support
 * @todo Cover hook_support_ticket_access in a separate test class.
 */
class SupportTicketAccessTest extends SupportTicketTestBase {
  protected function setUp() {
    parent::setUp();
    // Clear permissions for authenticated users.
    $this->config('user.role.' . RoleInterface::AUTHENTICATED_ID)->set('permissions', array())->save();
  }

  /**
   * Runs basic tests for support_ticket_access function.
   */
  function testSupportTicketAccess() {
    // Ensures user without 'access ticket' permission can do nothing.
    $web_user1 = $this->drupalCreateUser(array('create ticket ticket', 'edit any ticket ticket', 'delete any ticket ticket'));
    $support_ticket1 = $this->drupalCreateSupportTicket(array('type' => 'ticket'));
    $this->assertSupportTicketCreateAccess($support_ticket1->bundle(), FALSE, $web_user1);
    $this->assertSupportTicketAccess(array('view' => FALSE, 'update' => FALSE, 'delete' => FALSE), $support_ticket1, $web_user1);

    // Ensures user with 'bypass support_ticket access' permission can do everything.
    /*
    $web_user2 = $this->drupalCreateUser(array('bypass support_ticket access'));
    $support_ticket2 = $this->drupalCreateSupportTicket(array('type' => 'ticket'));
    $this->assertSupportTicketCreateAccess($support_ticket2->bundle(), TRUE, $web_user2);
    $this->assertSupportTicketAccess(array('view' => TRUE, 'update' => TRUE, 'delete' => TRUE), $support_ticket2, $web_user2);
    */

    // User cannot 'view own unpublished ticket'.
    $web_user3 = $this->drupalCreateUser(array('access support tickets'));
    $support_ticket3 = $this->drupalCreateSupportTicket(array('status' => 0, 'uid' => $web_user3->id()));
    $this->assertSupportTicketAccess(array('view' => FALSE), $support_ticket3, $web_user3);

    // User cannot create ticket without permission.
    $this->assertSupportTicketCreateAccess($support_ticket3->bundle(), FALSE, $web_user3);

    // User can 'view own unpublished ticket', but another user cannot.
    $web_user4 = $this->drupalCreateUser(array('access support tickets', 'view own unpublished support tickets'));
    $web_user5 = $this->drupalCreateUser(array('access support tickets', 'view own unpublished support tickets'));
    $support_ticket4 = $this->drupalCreateSupportTicket(array('status' => 0, 'uid' => $web_user4->id()));
    $this->assertSupportTicketAccess(array('view' => TRUE, 'update' => FALSE), $support_ticket4, $web_user4);
    $this->assertSupportTicketAccess(array('view' => FALSE), $support_ticket4, $web_user5);

    // Tests the default access provided for a published support_ticket.
    $support_ticket5 = $this->drupalCreateSupportTicket();
    $this->assertSupportTicketAccess(array('view' => TRUE, 'update' => FALSE, 'delete' => FALSE), $support_ticket5, $web_user3);

    // Tests the "edit any BUNDLE" and "delete any BUNDLE" permissions.
    $web_user6 = $this->drupalCreateUser(array('access support tickets', 'edit any ticket ticket', 'delete any ticket ticket'));
    $support_ticket6 = $this->drupalCreateSupportTicket(array('type' => 'ticket'));
    $this->assertSupportTicketAccess(array('view' => TRUE, 'update' => TRUE, 'delete' => TRUE), $support_ticket6, $web_user6);

    // Tests the "edit own BUNDLE" and "delete own BUNDLE" permission.
    $web_user7 = $this->drupalCreateUser(array('access support tickets', 'edit own ticket ticket', 'delete own ticket ticket'));
    // User should not be able to edit or delete support_tickets they do not own.
    $this->assertSupportTicketAccess(array('view' => TRUE, 'update' => FALSE, 'delete' => FALSE), $support_ticket6, $web_user7);

    // User should be able to edit or delete support_tickets they own.
    $support_ticket7 = $this->drupalCreateSupportTicket(array('type' => 'ticket', 'uid' => $web_user7->id()));
    $this->assertSupportTicketAccess(array('view' => TRUE, 'update' => TRUE, 'delete' => TRUE), $support_ticket7, $web_user7);
  }

}
