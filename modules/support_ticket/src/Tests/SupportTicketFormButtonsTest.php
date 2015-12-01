<?php

/**
 * @file
 * Contains \Drupal\support_ticket\Tests\SupportTicketFormButtonsTest.
 */

namespace Drupal\support_ticket\Tests;

/**
 * Tests all the different buttons on the support_ticket form.
 *
 * @group support
 */
class SupportTicketFormButtonsTest extends SupportTicketTestBase {

  use AssertButtonsTrait;

  /**
   * A normal logged in user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * A user with permission to bypass access content.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  protected function setUp() {
    parent::setUp();

    // Create a user that has no access to change the state of the support_ticket.
    $this->webUser = $this->drupalCreateUser(array('access support tickets', 'create ticket ticket', 'edit own ticket ticket'));
    // Create a user that has access to change the state of the support_ticket.
    // @todo 'administer support ticket types' is kinda silly as the admin check for the page -- look into the support_ticket/add permissions closer!
    $this->adminUser = $this->drupalCreateUser(array('access support tickets', 'administer support tickets', 'administer support ticket types', 'view own unpublished support tickets'));
  }

  /**
   * Tests that the right buttons are displayed for saving support_tickets.
   */
  function testSupportTicketFormButtons() {
    $support_ticket_storage = $this->container->get('entity.manager')->getStorage('support_ticket');
    // Login as administrative user.
    $this->drupalLogin($this->adminUser);

    // Verify the buttons on a support_ticket add form.
    $this->drupalGet('support_ticket/add/ticket');
    $this->assertButtons(array(t('Save and publish'), t('Save as unpublished')));

    // Save the support_ticket and assert it's published after clicking
    // 'Save and publish'.
    $edit = array('title[0][value]' => $this->randomString());
    $this->drupalPostForm('support_ticket/add/ticket', $edit, t('Save and publish'));

    // Get the support_ticket.
    $support_ticket_1 = $support_ticket_storage->load(1);
    $this->assertTrue($support_ticket_1->isPublished(), 'Support ticket is published');

    // Verify the buttons on a support_ticket edit form.
    $this->drupalGet('support_ticket/' . $support_ticket_1->id() . '/edit');
    $this->assertButtons(array(t('Save and keep published'), t('Save and unpublish')));

    // Save the support_ticket and verify it's still published after clicking
    // 'Save and keep published'.
    $this->drupalPostForm(NULL, $edit, t('Save and keep published'));
    $support_ticket_storage->resetCache(array(1));
    $support_ticket_1 = $support_ticket_storage->load(1);
    $this->assertTrue($support_ticket_1->isPublished(), 'Support ticket is published');

    // Save the support_ticket and verify it's unpublished after clicking
    // 'Save and unpublish'.
    $this->drupalPostForm('support_ticket/' . $support_ticket_1->id() . '/edit', $edit, t('Save and unpublish'));
    $support_ticket_storage->resetCache(array(1));
    $support_ticket_1 = $support_ticket_storage->load(1);
    $this->assertFalse($support_ticket_1->isPublished(), 'Support ticket is unpublished');

    // Verify the buttons on an unpublished support_ticket edit screen.
    $this->drupalGet('support_ticket/' . $support_ticket_1->id() . '/edit');
    $this->assertButtons(array(t('Save and keep unpublished'), t('Save and publish')));

    // Create a support_ticket as a normal user.
    $this->drupalLogout();
    $this->drupalLogin($this->webUser);

    // Verify the buttons for a normal user.
    $this->drupalGet('support_ticket/add/ticket');
    $this->assertButtons(array(t('Save')), FALSE);

    // Create the support_ticket.
    $edit = array('title[0][value]' => $this->randomString());
    $this->drupalPostForm('support_ticket/add/ticket', $edit, t('Save'));
    $support_ticket_2 = $support_ticket_storage->load(2);
    $this->assertTrue($support_ticket_2->isPublished(), 'Support ticket is published');

    // Login as an administrator and unpublish the support_ticket that just
    // was created by the normal user.
    $this->drupalLogout();
    $this->drupalLogin($this->adminUser);
    $this->drupalPostForm('support_ticket/' . $support_ticket_2->id() . '/edit', array(), t('Save and unpublish'));
    $support_ticket_storage->resetCache(array(2));
    $support_ticket_2 = $support_ticket_storage->load(2);
    $this->assertFalse($support_ticket_2->isPublished(), 'Support ticket is unpublished');

    // Login again as the normal user, save the support_ticket and verify
    // it's still unpublished.
    $this->drupalLogout();
    $this->drupalLogin($this->webUser);
    $this->drupalPostForm('support_ticket/' . $support_ticket_2->id() . '/edit', array(), t('Save'));
    $support_ticket_storage->resetCache(array(2));
    $support_ticket_2 = $support_ticket_storage->load(2);
    $this->assertFalse($support_ticket_2->isPublished(), 'Support ticket is still unpublished');
    $this->drupalLogout();

    // Set article content type default to unpublished. This will change the
    // the initial order of buttons and/or status of the support_ticket when creating
    // a support_ticket.
    $fields = \Drupal::entityManager()->getFieldDefinitions('support_ticket', 'ticket');
    $fields['status']->getConfig('ticket')
      ->setDefaultValue(FALSE)
      ->save();

    // Verify the buttons on a support_ticket add form for an administrator.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('support_ticket/add/ticket');
    $this->assertButtons(array(t('Save as unpublished'), t('Save and publish')));

    // Verify the support_ticket is unpublished by default for a normal user.
    $this->drupalLogout();
    $this->drupalLogin($this->webUser);
    $edit = array('title[0][value]' => $this->randomString());
    $this->drupalPostForm('support_ticket/add/ticket', $edit, t('Save'));
    $support_ticket_3 = $support_ticket_storage->load(3);
    $this->assertFalse($support_ticket_3->isPublished(), 'Support ticket is unpublished');
  }
}
