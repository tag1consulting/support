<?php

/**
 * @file
 * Contains \Drupal\support_ticket\SupportTicketViewsData.
 */

namespace Drupal\support_ticket;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the support ticket entity type.
 */
class SupportTicketViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['support_ticket_field_data']['table']['base']['weight'] = -10;
    $data['support_ticket_field_data']['table']['wizard_id'] = 'support_ticket';

    $data['support_ticket_field_data']['stid']['field']['argument'] = [
      'id' => 'support_ticket_stid',
      'name field' => 'title',
      'numeric' => TRUE,
      'validate type' => 'stid',
    ];

    $data['support_ticket_field_data']['title']['field']['default_formatter_settings'] = ['link_to_entity' => TRUE];

    $data['support_ticket_field_data']['title']['field']['link_to_support_ticket default'] = TRUE;

    $data['support_ticket_field_data']['type']['argument']['id'] = 'support_ticket_type';

    $data['support_ticket_field_data']['langcode']['help'] = t('The language of the ticket or translation.');

    $data['support_ticket_field_data']['status']['filter']['label'] = t('Published status');
    $data['support_ticket_field_data']['status']['filter']['type'] = 'yes-no';
    // Use status = 1 instead of status <> 0 in WHERE statement.
    $data['support_ticket_field_data']['status']['filter']['use_equal'] = TRUE;

    $data['support_ticket_field_data']['status_extra'] = array(
      'title' => t('Published status or admin user'),
      'help' => t('Filters out unpublished support tickets if the current user cannot view it.'),
      'filter' => array(
        'field' => 'status',
        'id' => 'support_ticket_status',
        'label' => t('Published status or admin user'),
      ),
    );

    $data['support_ticket_field_data']['locked']['filter']['label'] = t('Locked support ticket status');

    $data['support_ticket']['path'] = array(
      'field' => array(
        'title' => t('Path'),
        'help' => t('The aliased path to this support ticket.'),
        'id' => 'support_ticket_path',
      ),
    );

    $data['support_ticket']['support_ticket_bulk_form'] = array(
      'title' => t('Support ticket operations bulk form'),
      'help' => t('Add a form element that lets you run operations on multiple support tickets.'),
      'field' => array(
        'id' => 'support_ticket_bulk_form',
      ),
    );

    // Bogus fields for aliasing purposes.

    $data['support_ticket_field_data']['created_fulldate'] = array(
      'title' => t('Created date'),
      'help' => t('Date in the form of CCYYMMDD.'),
      'argument' => array(
        'field' => 'created',
        'id' => 'date_fulldate',
      ),
    );

    $data['support_ticket_field_data']['created_year_month'] = array(
      'title' => t('Created year + month'),
      'help' => t('Date in the form of YYYYMM.'),
      'argument' => array(
        'field' => 'created',
        'id' => 'date_year_month',
      ),
    );

    $data['support_ticket_field_data']['created_year'] = array(
      'title' => t('Created year'),
      'help' => t('Date in the form of YYYY.'),
      'argument' => array(
        'field' => 'created',
        'id' => 'date_year',
      ),
    );

    $data['support_ticket_field_data']['created_month'] = array(
      'title' => t('Created month'),
      'help' => t('Date in the form of MM (01 - 12).'),
      'argument' => array(
        'field' => 'created',
        'id' => 'date_month',
      ),
    );

    $data['support_ticket_field_data']['created_day'] = array(
      'title' => t('Created day'),
      'help' => t('Date in the form of DD (01 - 31).'),
      'argument' => array(
        'field' => 'created',
        'id' => 'date_day',
      ),
    );

    $data['support_ticket_field_data']['created_week'] = array(
      'title' => t('Created week'),
      'help' => t('Date in the form of WW (01 - 53).'),
      'argument' => array(
        'field' => 'created',
        'id' => 'date_week',
      ),
    );

    $data['support_ticket_field_data']['changed_fulldate'] = array(
      'title' => t('Updated date'),
      'help' => t('Date in the form of CCYYMMDD.'),
      'argument' => array(
        'field' => 'changed',
        'id' => 'date_fulldate',
      ),
    );

    $data['support_ticket_field_data']['changed_year_month'] = array(
      'title' => t('Updated year + month'),
      'help' => t('Date in the form of YYYYMM.'),
      'argument' => array(
        'field' => 'changed',
        'id' => 'date_year_month',
      ),
    );

    $data['support_ticket_field_data']['changed_year'] = array(
      'title' => t('Updated year'),
      'help' => t('Date in the form of YYYY.'),
      'argument' => array(
        'field' => 'changed',
        'id' => 'date_year',
      ),
    );

    $data['support_ticket_field_data']['changed_month'] = array(
      'title' => t('Updated month'),
      'help' => t('Date in the form of MM (01 - 12).'),
      'argument' => array(
        'field' => 'changed',
        'id' => 'date_month',
      ),
    );

    $data['support_ticket_field_data']['changed_day'] = array(
      'title' => t('Updated day'),
      'help' => t('Date in the form of DD (01 - 31).'),
      'argument' => array(
        'field' => 'changed',
        'id' => 'date_day',
      ),
    );

    $data['support_ticket_field_data']['changed_week'] = array(
      'title' => t('Updated week'),
      'help' => t('Date in the form of WW (01 - 53).'),
      'argument' => array(
        'field' => 'changed',
        'id' => 'date_week',
      ),
    );

    $data['support_ticket_field_data']['uid']['help'] = t('The user authoring the support ticket. If you need more fields than the uid add the support ticket: author relationship');
    $data['support_ticket_field_data']['uid']['filter']['id'] = 'user_name';
    $data['support_ticket_field_data']['uid']['relationship']['title'] = t('Support ticket author');
    $data['support_ticket_field_data']['uid']['relationship']['help'] = t('Relate support ticket to the user who created it.');
    $data['support_ticket_field_data']['uid']['relationship']['label'] = t('author');

    $data['support_ticket']['support_ticket_listing_empty'] = array(
      'title' => t('Empty Support Ticket listing behavior'),
      'help' => t('Provides a link to the support ticket add page.'),
      'area' => array(
        'id' => 'support_ticket_listing_empty',
      ),
    );

    $data['support_ticket_field_data']['uid_revision']['title'] = t('User has a revision');
    $data['support_ticket_field_data']['uid_revision']['help'] = t('All support tickets where a certain user has a revision');
    $data['support_ticket_field_data']['uid_revision']['real field'] = 'stid';
    $data['support_ticket_field_data']['uid_revision']['filter']['id'] = 'support_ticket_uid_revision';
    $data['support_ticket_field_data']['uid_revision']['argument']['id'] = 'support_ticket_uid_revision';

    $data['support_ticket_field_revision']['table']['wizard_id'] = 'support_ticket_revision';

    // Advertise this table as a possible base table.
    $data['support_ticket_field_revision']['table']['base']['help'] = t('Support ticket revision is a history of changes to support tickets.');
    $data['support_ticket_field_revision']['table']['base']['defaults']['title'] = 'title';

    $data['support_ticket_field_revision']['stid']['argument'] = [
      'id' => 'support_ticket_stid',
      'numeric' => TRUE,
    ];
    $data['support_ticket_field_revision']['stid']['relationship']['id'] = 'standard';
    $data['support_ticket_field_revision']['stid']['relationship']['base'] = 'support_ticket_field_data';
    $data['support_ticket_field_revision']['stid']['relationship']['base field'] = 'stid';
    $data['support_ticket_field_revision']['stid']['relationship']['title'] = t('Support ticket');
    $data['support_ticket_field_revision']['stid']['relationship']['label'] = t('Get the actual support ticket from a support ticket revision.');

    $data['support_ticket_field_revision']['vid'] = array(
      'argument' => array(
        'id' => 'support_ticket_vid',
        'numeric' => TRUE,
      ),
      'relationship' => array(
        'id' => 'standard',
        'base' => 'support_ticket_field_data',
        'base field' => 'vid',
        'title' => t('Support ticket'),
        'label' => t('Get the actual support ticket from a support ticket revision.'),
      ),
    ) + $data['support_ticket_field_revision']['vid'];

    $data['support_ticket_field_revision']['langcode']['help'] = t('The language the original ticket is in.');

    $data['support_ticket_revision']['revision_uid']['help'] = t('Relate a ticket revision to the user who created the revision.');
    $data['support_ticket_revision']['revision_uid']['relationship']['label'] = t('revision user');

    $data['support_ticket_field_revision']['table']['wizard_id'] = 'support_ticket_field_revision';

    $data['support_ticket_field_revision']['table']['join']['support_ticket_field_data']['left_field'] = 'vid';
    $data['support_ticket_field_revision']['table']['join']['support_ticket_field_data']['field'] = 'vid';

    $data['support_ticket_field_revision']['status']['filter']['label'] = t('Published');
    $data['support_ticket_field_revision']['status']['filter']['type'] = 'yes-no';
    $data['support_ticket_field_revision']['status']['filter']['use_equal'] = TRUE;

    $data['support_ticket_field_revision']['langcode']['help'] = t('The language of the ticket or translation.');

    $data['support_ticket_field_revision']['link_to_revision'] = array(
      'field' => array(
        'title' => t('Link to revision'),
        'help' => t('Provide a simple link to the revision.'),
        'id' => 'support_ticket_revision_link',
        'click sortable' => FALSE,
      ),
    );

    $data['support_ticket_field_revision']['revert_revision'] = array(
      'field' => array(
        'title' => t('Link to revert revision'),
        'help' => t('Provide a simple link to revert to the revision.'),
        'id' => 'support_ticket_revision_link_revert',
        'click sortable' => FALSE,
      ),
    );

    $data['support_ticket_field_revision']['delete_revision'] = array(
      'field' => array(
        'title' => t('Link to delete revision'),
        'help' => t('Provide a simple link to delete the support ticket revision.'),
        'id' => 'support_ticket_revision_link_delete',
        'click sortable' => FALSE,
      ),
    );

    // Add search table, fields, filters, etc., but only if a page using the
    // support_ticket_search plugin is enabled.
    if (\Drupal::moduleHandler()->moduleExists('search')) {
      $enabled = FALSE;
      $search_page_repository = \Drupal::service('search.search_page_repository');
      foreach ($search_page_repository->getActiveSearchpages() as $page) {
        if ($page->getPlugin()->getPluginId() == 'support_ticket_search') {
          $enabled = TRUE;
          break;
        }
      }

      if ($enabled) {
        $data['support_ticket_search_index']['table']['group'] = t('Search');

        // Automatically join to the support_ticket_field_data table.
        // Use a Views table alias to allow other modules to use this table too,
        // if they use the search index.
        $data['support_ticket_search_index']['table']['join'] = array(
          'support_ticket_field_data' => array(
            'left_field' => 'stid',
            'field' => 'sid',
            'table' => 'search_index',
            'extra' => "support_ticket_search_index.type = 'support_ticket_search' AND support_ticket_search_index.langcode = support_ticket_field_data.langcode",
          )
        );

        $data['support_ticket_search_total']['table']['join'] = array(
          'support_ticket_search_index' => array(
            'left_field' => 'word',
            'field' => 'word',
          ),
        );

        $data['search_ticket_dataset']['table']['join'] = array(
          'support_ticket_field_data' => array(
            'left_field' => 'sid',
            'left_table' => 'support_ticket_search_index',
            'field' => 'sid',
            'table' => 'search_dataset',
            'extra' => 'support_ticket_search_index.type = search_ticket_dataset.type AND support_ticket_search_index.langcode = search_ticket_dataset.langcode',
            'type' => 'INNER',
          ),
        );

        $data['support_ticket_search_index']['score'] = array(
          'title' => t('Score'),
          'help' => t('The score of the search item. This will not be used if the search filter is not also present.'),
          'field' => array(
            'id' => 'search_score',
            'float' => TRUE,
            'no group by' => TRUE,
          ),
          'sort' => array(
            'id' => 'search_score',
            'no group by' => TRUE,
          ),
        );

        $data['support_ticket_search_index']['keys'] = array(
          'title' => t('Search Keywords'),
          'help' => t('The keywords to search for.'),
          'filter' => array(
            'id' => 'search_keywords',
            'no group by' => TRUE,
            'search_type' => 'support_ticket_search',
          ),
          'argument' => array(
            'id' => 'search',
            'no group by' => TRUE,
            'search_type' => 'support_ticket_search',
          ),
        );

      }
    }

    return $data;
  }

}
