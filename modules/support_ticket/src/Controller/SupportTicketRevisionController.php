<?php

/**
 * @file
 * Contains \Drupal\support_ticket\Controller\SupportTicketRevisionController.
 */

namespace Drupal\support_ticket\Controller;

use Drupal\Core\Url;
use Drupal\support_ticket\SupportTicketInterface;
use Drupal\diff\EntityComparisonBase;
use Drupal\Component\Utility\Xss;

/**
 * Returns responses for Support Ticket Revision routes.
 */
class SupportTicketRevisionController extends EntityComparisonBase {

  /**
   * Returns a form for revision overview page.
   *
   * @param SupportTicketInterface $support_ticket
   *   The support ticket whose revisions are inspected.
   *
   * @return array
   *   Render array containing the revisions table for $support_ticket.
   */
  public function revisionOverview(SupportTicketInterface $support_ticket) {
    return $this->formBuilder()->getForm('Drupal\support_ticket\Form\RevisionOverviewForm', $support_ticket);
  }

  /**
   * Returns a table which shows the differences between two support ticket revisions.
   *
   * @param SupportTicketInterface $support_ticket
   *   The support ticket whose revisions are compared.
   * @param $left_vid
   *   Vid of the support ticket revision from the left.
   * @param $right_vid
   *   Vid of the support ticket revision from the right.
   * @param $filter
   *   If $filter == 'raw' raw text is compared (including html tags)
   *   If filter == 'raw-plain' markdown function is applied to the text before comparison.
   *
   * @return array
   *   Table showing the diff between the two support ticket revisions.
   */
  public function compareSupportTicketRevisions(SupportTicketInterface $support_ticket, $left_vid, $right_vid, $filter) {
    $diff_rows = array();
    $build = array(
      '#title' => $this->t('Revisions for %title', array('%title' => $support_ticket->label())),
    );
    if (!in_array($filter, array('raw', 'raw-plain'))) {
      $filter = 'raw';
    }
    elseif ($filter == 'raw-plain') {
      $filter = 'raw_plain';
    }
    // Support Ticket storage service.
    $storage = $this->entityManager()->getStorage('support_ticket');
    $left_revision = $storage->loadRevision($left_vid);
    $right_revision = $storage->loadRevision($right_vid);
    $vids = $storage->revisionIds($support_ticket);
    $diff_rows[] = $this->buildRevisionsNavigation($support_ticket->id(), $vids, $left_vid, $right_vid);
    $diff_rows[] = $this->buildMarkdownNavigation($support_ticket->id(), $left_vid, $right_vid, $filter);
    $diff_header = $this->buildTableHeader($left_revision, $right_revision);

    // Perform comparison only if both support ticket revisions loaded successfully.
    if ($left_revision != FALSE && $right_revision != FALSE) {
      $fields = $this->compareRevisions($left_revision, $right_revision);
      $support_ticket_base_fields = $this->entityManager()->getBaseFieldDefinitions('support_ticket');
      // Check to see if we need to display certain fields or not based on
      // selected view mode display settings.
      foreach ($fields as $field_name => $field) {
        // If we are dealing with support tickets only compare those fields
        // set as visible from the selected view mode.
        $view_mode = $this->config->get('support_ticket_type_settings.' . $support_ticket->getType() . '.view_mode');
        // If no view mode is selected use the default view mode.
        if ($view_mode == NULL) {
          $view_mode = 'default';
        }
        $visible = entity_get_display('support_ticket', $support_ticket->getType(), $view_mode)->getComponent($field_name);
        if ($visible == NULL && !array_key_exists($field_name, $support_ticket_base_fields)) {
          unset($fields[$field_name]);
        }
      }
      // Build the diff rows for each field and append the field rows
      // to the table rows.
      foreach ($fields as $field) {
        $field_label_row = '';
        if (!empty($field['#name'])) {
          $field_label_row = array(
            'data' => $this->t('Changes to %name', array('%name' => $field['#name'])),
            'colspan' => 4,
            'class' => array('field-name'),
          );
        }
        $field_diff_rows = $this->getRows(
          $field['#states'][$filter]['#left'],
          $field['#states'][$filter]['#right']
        );

        // Add the field label to the table only if there are changes to that field.
        if (!empty($field_diff_rows) && !empty($field_label_row)) {
          $diff_rows[] = array($field_label_row);
        }

        // Add field diff rows to the table rows.
        $diff_rows = array_merge($diff_rows, $field_diff_rows);
      }

      // Add the CSS for the diff.
      $build['#attached']['library'][] = 'diff/diff.general';
      $theme = $this->config->get('general_settings.theme');
      if ($theme) {
        if ($theme == 'default') {
          $build['#attached']['library'][] = 'diff/diff.default';
        }
        elseif ($theme == 'github') {
          $build['#attached']['library'][] = 'diff/diff.github';
        }
      }
      // If the setting could not be loaded or is missing use the default theme.
      elseif ($theme == NULL) {
        $build['#attached']['library'][] = 'diff/diff.github';
      }

      $build['diff'] = array(
        '#type' => 'table',
        '#header' => $diff_header,
        '#rows' => $diff_rows,
        '#empty' => $this->t('No visible changes'),
        '#attributes' => array(
          'class' => array('diff'),
        ),
      );

      $build['back'] = array(
        '#type' => 'link',
        '#attributes' => array(
          'class' => array(
            'button',
            'diff-button',
          ),
        ),
        '#title' => $this->t('Back to Revision Overview'),
        '#url' => Url::fromRoute('entity.support_ticket.version_history', ['support_ticket' => $support_ticket->id()]),
      );

      return $build;
    }
    else {
      // @todo When task 'Convert drupal_set_message() to a service' (2278383)
      //   will be merged use the corresponding service instead.
      drupal_set_message($this->t('Selected support ticket revisions could not be loaded.'), 'error');
    }
  }

  /**
   * Build the header for the diff table.
   *
   * @param $left_revision
   *   Revision from the left hand side.
   * @param $right_revision
   *   Revision from the right hand side.
   *
   * @return array
   *   Header for Diff table.
   */
  protected function buildTableHeader($left_revision, $right_revision) {
    $revisions = array($left_revision, $right_revision);
    $header = array();

    foreach ($revisions as $revision) {
      $revision_log = $this->nonBreakingSpace;

      if ($revision->revision_log->value != '') {
        $revision_log = Xss::filter($revision->revision_log->value);
      }
      $username = array(
        '#theme' => 'username',
        '#account' => $revision->uid->entity,
      );
      $revision_date = $this->date->format($revision->getRevisionCreationTime(), 'short');
      $revision_link = $this->t($revision_log . '@date', array(
        '@date' => $this->l($revision_date, Url::fromRoute('entity.support_ticket.revision', array(
          'support_ticket' => $revision->id(),
          'support_ticket_revision' => $revision->getRevisionId(),
        ))),
      ));
      // @todo When theming think about where in the table to integrate this
      //   link to the revision user. There is some issue about multi-line headers
      //   for theme table.
      // $header[] = array(
      //   'data' => $this->t('by' . '!username', array('!username' => drupal_render($username))),
      //   'colspan' => 1,
      // );
      $header[] = array(
        'data' => array('#markup' => $this->nonBreakingSpace),
        'colspan' => 1,
      );
      $header[] = array(
        'data' => array('#markup' => $revision_link),
        'colspan' => 1,
      );
    }

    return $header;
  }

  /**
   * Returns the navigation row for diff table.
   */
  protected function buildRevisionsNavigation($stid, $vids, $left_vid, $right_vid) {
    $revisions_count = count($vids);
    $i = 0;

    $row = array();
    // Find the previous revision.
    while ($left_vid > $vids[$i]) {
      $i += 1;
    }
    if ($i != 0) {
      // Second column.
      $row[] = array(
        'data' => $this->l(
          $this->t('< Previous difference'),
          Url::fromRoute('support_ticket.revisions_diff',
          array(
            'support_ticket' => $stid,
            'left_vid' => $vids[$i - 1],
            'right_vid' => $left_vid,
          ))
        ),
        'colspan' => 2,
        'class' => 'rev-navigation',
      );
    }
    else {
      // Second column.
      $row[] = $this->nonBreakingSpace;
    }
    // Third column.
    $row[] = $this->nonBreakingSpace;
    // Find the next revision.
    $i = 0;
    while ($i < $revisions_count && $right_vid >= $vids[$i]) {
      $i += 1;
    }
    if ($revisions_count != $i && $vids[$i - 1] != $vids[$revisions_count - 1]) {
      // Forth column.
      $row[] = array(
        'data' => $this->l(
          $this->t('Next difference >'),
          Url::fromRoute('support_ticket.revisions_diff',
          array(
            'support_ticket' => $stid,
            'left_vid' => $right_vid,
            'right_vid' => $vids[$i],
          ))
        ),
        'colspan' => 2,
        'class' => 'rev-navigation',
      );
    }
    else {
      // Forth column.
      $row[] = $this->nonBreakingSpace;
    }

    // If there are only 2 revision return an empty row.
    if ($revisions_count == 2) {
      return array();
    }
    else {
      return $row;
    }
  }

  /**
   * Builds a table row with navigation between raw and raw-plain formats.
   */
  protected function buildMarkdownNavigation($stid, $left_vid, $right_vid, $active_filter) {

    $links['raw'] = array(
      'title' => $this->t('Standard'),
      'url' => Url::fromRoute('support_ticket.revisions_diff', array(
        'support_ticket' => $stid,
        'left_vid' => $left_vid,
        'right_vid' => $right_vid,
      )),
    );
    $links['raw_plain'] = array(
      'title' => $this->t('Markdown'),
      'url' => Url::fromRoute('support_ticket.revisions_diff', array(
        'support_ticket' => $stid,
        'left_vid' => $left_vid,
        'right_vid' => $right_vid,
        'filter' => 'raw-plain',
      )),
    );

    // Set as the first element the current filter.
    $filter = $links[$active_filter];
    unset($links[$active_filter]);
    array_unshift($links, $filter);

    $row[] = array(
      'data' => array(
        '#type' => 'operations',
        '#links' => $links,
      ),
      'colspan' => 4,
    );

    return $row;
  }

}
