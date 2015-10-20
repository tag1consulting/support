<?php

/**
 * @file
 * Builds placeholder replacement tokens for support_ticket-related data.
 */

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\user\Entity\User;

/**
 * Implements hook_token_info().
 */
function support_ticket_token_info() {
  $type = array(
    'name' => t('Support tickets'),
    'description' => t('Tokens related to individual support tickets.'),
    'needs-data' => 'support_ticket',
  );

  // Tokens for support tickets.
  $support_ticket['stid'] = array(
    'name' => t("Support ticket ID"),
    'description' => t('The unique ID of the support ticket.'),
  );
  $support_ticket['vid'] = array(
    'name' => t("Revision ID"),
    'description' => t("The unique ID of the support ticket's latest revision."),
  );
  $support_ticket['type'] = array(
    'name' => t("Support ticket type"),
  );
  $support_ticket['type-name'] = array(
    'name' => t("Support ticket type name"),
    'description' => t("The human-readable name of the support ticket type."),
  );
  $support_ticket['title'] = array(
    'name' => t("Title"),
  );
  $support_ticket['body'] = array(
    'name' => t("Ticket body"),
    'description' => t("The main body text of the support ticket."),
  );
  $support_ticket['summary'] = array(
    'name' => t("Summary"),
    'description' => t("The summary of the support ticket's main body text."),
  );
  $support_ticket['langcode'] = array(
    'name' => t('Language code'),
    'description' => t('The language code of the language the support ticket is written in.'),
  );
  $support_ticket['url'] = array(
    'name' => t("URL"),
    'description' => t("The URL of the support ticket."),
  );
  $support_ticket['edit-url'] = array(
    'name' => t("Edit URL"),
    'description' => t("The URL of the support ticket's edit page."),
  );

  // Chained tokens for support tickets.
  $support_ticket['created'] = array(
    'name' => t("Date created"),
    'type' => 'date',
  );
  $support_ticket['changed'] = array(
    'name' => t("Date changed"),
    'description' => t("The date the support ticket was most recently updated."),
    'type' => 'date',
  );
  $support_ticket['author'] = array(
    'name' => t("Author"),
    'type' => 'user',
  );

  return array(
    'types' => array('support_ticket' => $type),
    'tokens' => array('support_ticket' => $support_ticket),
  );
}

/**
 * Implements hook_tokens().
 */
function support_ticket_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();

  $url_options = array('absolute' => TRUE);
  if (isset($options['langcode'])) {
    $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = LanguageInterface::LANGCODE_DEFAULT;
  }

  $replacements = array();

  if ($type == 'support_ticket' && !empty($data['support_ticket'])) {
    /** @var \Drupal\support_ticket\SupportTicketInterface $support_ticket */
    $support_ticket = $data['support_ticket'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        // Simple key values on the support ticket.
        case 'stid':
          $replacements[$original] = $support_ticket->id();
          break;

        case 'vid':
          $replacements[$original] = $support_ticket->getRevisionId();
          break;

        case 'type':
          $replacements[$original] = $support_ticket->getType();
          break;

        case 'type-name':
          $type_name = support_ticket_get_type_label($support_ticket);
          $replacements[$original] = $type_name;
          break;

        case 'title':
          $replacements[$original] = $support_ticket->getTitle();
          break;

        case 'body':
        case 'summary':
          $translation = \Drupal::entityManager()->getTranslationFromContext($support_ticket, $langcode, array('operation' => 'support_ticket_tokens'));
          if ($translation->hasField('body') && ($items = $translation->get('body')) && !$items->isEmpty()) {
            $item = $items[0];
            // If the summary was requested and is not empty, use it.
            if ($name == 'summary' && !empty($item->summary)) {
              $output = $item->summary_processed;
            }
            // Attempt to provide a suitable version of the 'body' field.
            else {
              $output = $item->processed;
              // A summary was requested.
              if ($name == 'summary') {
                // Generate an optionally trimmed summary of the body field.

                // Get the 'trim_length' size used for the 'teaser' mode, if
                // present, or use the default trim_length size.
                $display_options = entity_get_display('support_ticket', $support_ticket->getType(), 'teaser')->getComponent('body');
                if (isset($display_options['settings']['trim_length'])) {
                  $length = $display_options['settings']['trim_length'];
                }
                else {
                  $settings = \Drupal::service('plugin.manager.field.formatter')->getDefaultSettings('text_summary_or_trimmed');
                  $length = $settings['trim_length'];
                }

                $output = text_summary($output, $item->format, $length);
              }
            }
            // "processed" returns a \Drupal\Component\Render\MarkupInterface
            // via check_markup().
            $replacements[$original] = $output;
          }
          break;

        case 'langcode':
          $replacements[$original] = $support_ticket->language()->getId();
          break;

        case 'url':
          $replacements[$original] = $support_ticket->url('canonical', $url_options);
          break;

        case 'edit-url':
          $replacements[$original] = $support_ticket->url('edit-form', $url_options);
          break;

        // Default values for the chained tokens handled below.
        case 'author':
          $account = $support_ticket->getOwner() ? $support_ticket->getOwner() : User::load(0);
          $bubbleable_metadata->addCacheableDependency($account);
          $replacements[$original] = $account->label();
          break;

        case 'created':
          $date_format = DateFormat::load('medium');
          $bubbleable_metadata->addCacheableDependency($date_format);
          $replacements[$original] = format_date($support_ticket->getCreatedTime(), 'medium', '', NULL, $langcode);
          break;

        case 'changed':
          $date_format = DateFormat::load('medium');
          $bubbleable_metadata->addCacheableDependency($date_format);
          $replacements[$original] = format_date($support_ticket->getChangedTime(), 'medium', '', NULL, $langcode);
          break;
      }
    }

    if ($author_tokens = $token_service->findWithPrefix($tokens, 'author')) {
      $replacements += $token_service->generate('user', $author_tokens, array('user' => $support_ticket->getOwner()), $options, $bubbleable_metadata);
    }

    if ($created_tokens = $token_service->findWithPrefix($tokens, 'created')) {
      $replacements += $token_service->generate('date', $created_tokens, array('date' => $support_ticket->getCreatedTime()), $options, $bubbleable_metadata);
    }

    if ($changed_tokens = $token_service->findWithPrefix($tokens, 'changed')) {
      $replacements += $token_service->generate('date', $changed_tokens, array('date' => $support_ticket->getChangedTime()), $options, $bubbleable_metadata);
    }
  }

  return $replacements;
}
