<?php

/**
 * @file
 * Contains \Drupal\support_ticket\Plugin\migrate\source\d6\SupportTicketReference.
 */

namespace Drupal\support_ticket\Plugin\migrate\source\d6;

use Drupal\migrate\Row;
use Drupal\migrate\Plugin\SourceEntityInterface;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/*
mysql> describe support_reference;
+-------+------------------+------+-----+---------+-------+
| Field | Type             | Null | Key | Default | Extra |
+-------+------------------+------+-----+---------+-------+
| nid   | int(10) unsigned | NO   | PRI | 0       |       |
| rnid  | int(10) unsigned | NO   | PRI | 0       |       |
+-------+------------------+------+-----+---------+-------+
*/

/**
 * Drupal 6 support ticket source from database.
 *
 * @MigrateSource(
 *   id = "d6_support_ticket_reference"
 * )
 */
class SupportTicketReference extends DrupalSqlBase implements SourceEntityInterface {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Select support ticket / node in its last revision.
    $query = $this->select('support_reference', 'sr')
      ->distinct()
      ->fields('sr', array(
        'nid',
        'rnid',
      ));

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = array(
      'nid' => $this->t('Support Ticket ID'),
      'rnid' => $this->t('Referenced Support Ticket ID'),
    );
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Select all related tickets (rnid) to the current ticket (nid)
    $query = $this->select('support_reference', 'sr')
      ->fields('sr', array('rnid'))
      ->condition('sr.nid', $row->getSourceProperty('nid'));
    $row->setSourceProperty('rnid', $query->execute()->fetchCol());
    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return array(
      'nid' => array(
        'type' => 'integer',
        'alias' => 'sr',
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function bundleMigrationRequired() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function entityTypeId() {
    return 'node';
  }

}
