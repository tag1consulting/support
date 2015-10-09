<?php

/**
 * @file
 * Contains \Drupal\support_ticket\Tests\SupportTicketTokenReplaceTest.
 */

namespace Drupal\support_ticket\Tests;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\system\Tests\System\TokenReplaceUnitTestBase;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Generates text using placeholders for dummy content to check support_ticket token
 * replacement.
 *
 * @group support_ticket
 */
class SupportTicketTokenReplaceTest extends TokenReplaceUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('support_ticket', 'filter');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(SupportTicketTokenReplaceTest::$modules);
    $this->installEntitySchema('support_ticket');

    $support_ticket_type = entity_create('support_ticket_type', array('type' => 'test', 'name' => 'Test'));
    $support_ticket_type->save();
    support_ticket_add_body_field($support_ticket_type);
  }

  /**
   * Creates a support_ticket, then tests the tokens generated from it.
   */
  function testSupportTicketTokenReplacement() {
    $url_options = array(
      'absolute' => TRUE,
      'language' => $this->interfaceLanguage,
    );

    // Create a user and a support_ticket.
    $account = $this->createUser();
    /* @var $support_ticket \Drupal\support_ticket\SupportTicketInterface */
    $support_ticket = entity_create('support_ticket', array(
      'support_ticket_type' => 'test',
      'tnid' => 0,
      'uid' => $account->id(),
      'title' => '<blink>Blinking Text</blink>',
      'body' => array(array('value' => $this->randomMachineName(32), 'summary' => $this->randomMachineName(16), 'format' => 'plain_text')),
    ));
    $support_ticket->save();

    // Generate and test sanitized tokens.
    $tests = array();
    $tests['[support_ticket:stid]'] = $support_ticket->id();
    $tests['[support_ticket:vid]'] = $support_ticket->getRevisionId();
    $tests['[support_ticket:type]'] = 'test';
    $tests['[support_ticket:type-name]'] = 'Test';
    $tests['[support_ticket:title]'] = SafeMarkup::checkPlain($support_ticket->getTitle());
    $tests['[support_ticket:body]'] = $support_ticket->body->processed;
    //$tests['[support_ticket:summary]'] = $support_ticket->body->summary_processed;
    $tests['[support_ticket:langcode]'] = SafeMarkup::checkPlain($support_ticket->language()->getId());
    $tests['[support_ticket:url]'] = $support_ticket->url('canonical', $url_options);
    $tests['[support_ticket:edit-url]'] = $support_ticket->url('edit-form', $url_options);
    $tests['[support_ticket:author]'] = SafeMarkup::checkPlain($account->getUsername());
    $tests['[support_ticket:author:uid]'] = $support_ticket->getOwnerId();
    $tests['[support_ticket:author:name]'] = SafeMarkup::checkPlain($account->getUsername());
    $tests['[support_ticket:created:since]'] = \Drupal::service('date.formatter')->formatTimeDiffSince($support_ticket->getCreatedTime(), array('langcode' => $this->interfaceLanguage->getId()));
    $tests['[support_ticket:changed:since]'] = \Drupal::service('date.formatter')->formatTimeDiffSince($support_ticket->getChangedTime(), array('langcode' => $this->interfaceLanguage->getId()));

    $base_bubbleable_metadata = BubbleableMetadata::createFromObject($support_ticket);

    $metadata_tests = [];
    $metadata_tests['[support_ticket:stid]'] = $base_bubbleable_metadata;
    $metadata_tests['[support_ticket:vid]'] = $base_bubbleable_metadata;
    $metadata_tests['[support_ticket:type]'] = $base_bubbleable_metadata;
    $metadata_tests['[support_ticket:type-name]'] = $base_bubbleable_metadata;
    $metadata_tests['[support_ticket:title]'] = $base_bubbleable_metadata;
    $metadata_tests['[support_ticket:body]'] = $base_bubbleable_metadata;
    //$metadata_tests['[support_ticket:summary]'] = $base_bubbleable_metadata;
    $metadata_tests['[support_ticket:langcode]'] = $base_bubbleable_metadata;
    $metadata_tests['[support_ticket:url]'] = $base_bubbleable_metadata;
    $metadata_tests['[support_ticket:edit-url]'] = $base_bubbleable_metadata;
    $bubbleable_metadata = clone $base_bubbleable_metadata;
    $metadata_tests['[support_ticket:author]'] = $bubbleable_metadata->addCacheTags(['user:1']);
    $metadata_tests['[support_ticket:author:uid]'] = $bubbleable_metadata;
    $metadata_tests['[support_ticket:author:name]'] = $bubbleable_metadata;
    $bubbleable_metadata = clone $base_bubbleable_metadata;
    $metadata_tests['[support_ticket:created:since]'] = $bubbleable_metadata->setCacheMaxAge(0);
    $metadata_tests['[support_ticket:changed:since]'] = $bubbleable_metadata;

    // Test to make sure that we generated something for each token.
    $this->assertFalse(in_array(0, array_map('strlen', $tests)), 'No empty tokens generated.');

    foreach ($tests as $input => $expected) {
      $bubbleable_metadata = new BubbleableMetadata();
      $output = $this->tokenService->replace($input, array('support_ticket' => $support_ticket), array('langcode' => $this->interfaceLanguage->getId()), $bubbleable_metadata);
      $this->assertEqual($output, $expected, format_string('Sanitized support_ticket token %token replaced.', array('%token' => $input)));
      $this->assertEqual($bubbleable_metadata, $metadata_tests[$input]);
    }

    // Generate and test unsanitized tokens.
    $tests['[support_ticket:title]'] = $support_ticket->getTitle();
    $tests['[support_ticket:body]'] = $support_ticket->body->value;
    //$tests['[support_ticket:summary]'] = $support_ticket->body->summary;
    $tests['[support_ticket:langcode]'] = $support_ticket->language()->getId();
    $tests['[support_ticket:author:name]'] = $account->getUsername();

    foreach ($tests as $input => $expected) {
      $output = $this->tokenService->replace($input, array('support_ticket' => $support_ticket), array('langcode' => $this->interfaceLanguage->getId(), 'sanitize' => FALSE));
      $this->assertEqual($output, $expected, format_string('Unsanitized support_ticket token %token replaced.', array('%token' => $input)));
    }

    // Repeat for a support_ticket without a summary.
    $support_ticket = entity_create('support_ticket', array(
      'support_ticket_type' => 'test',
      'uid' => $account->id(),
      'title' => '<blink>Blinking Text</blink>',
      'body' => array(array('value' => $this->randomMachineName(32), 'format' => 'plain_text')),
    ));
    $support_ticket->save();

    // Generate and test sanitized token - use full body as expected value.
    $tests = array();
    // @todo: Find an appropriate way to do the rest of these tests.
    //$tests['[support_ticket:summary]'] = $support_ticket->body->processed;

    // Test to make sure that we generated something for each token.
    $this->assertFalse(in_array(0, array_map('strlen', $tests)), 'No empty tokens generated for support_ticket without a summary.');

    foreach ($tests as $input => $expected) {
      $output = $this->tokenService->replace($input, array('support_ticket' => $support_ticket), array('language' => $this->interfaceLanguage));
      $this->assertEqual($output, $expected, format_string('Sanitized support_ticket token %token replaced for support_ticket without a summary.', array('%token' => $input)));
    }

    // Generate and test unsanitized tokens.
    //$tests['[support_ticket:summary]'] = $support_ticket->body->value;

    foreach ($tests as $input => $expected) {
      $output = $this->tokenService->replace($input, array('support_ticket' => $support_ticket), array('language' => $this->interfaceLanguage, 'sanitize' => FALSE));
      $this->assertEqual($output, $expected, format_string('Unsanitized support_ticket token %token replaced for support_ticket without a summary.', array('%token' => $input)));
    }
  }

}
