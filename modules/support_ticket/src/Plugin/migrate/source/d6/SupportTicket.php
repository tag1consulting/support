<?php

/**
 * @file
 * Contains \Drupal\support_ticket\Plugin\migrate\source\d6\SupportTicket.
 */

namespace Drupal\support_ticket\Plugin\migrate\source\d6;

use Drupal\migrate\Row;
use Drupal\migrate\Plugin\SourceEntityInterface;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/*
describe support_ticket;
+------------+------------------+------+-----+---------+-------+
| Field      | Type             | Null | Key | Default | Extra |
+------------+------------------+------+-----+---------+-------+
| nid        | int(11)          | NO   | PRI | 0       |       |
| message_id | varchar(255)     | YES  | MUL | NULL    |       |
| state      | int(3) unsigned  | NO   | MUL | 0       |       |
| priority   | int(3) unsigned  | NO   |     | 0       |       |
| client     | int(10) unsigned | NO   |     | 0       |       |
| assigned   | int(10) unsigned | NO   |     | 0       |       |
+------------+------------------+------+-----+---------+-------+

describe support_states;
+-----------+------------------+------+-----+---------+----------------+
| Field     | Type             | Null | Key | Default | Extra          |
+-----------+------------------+------+-----+---------+----------------+
| sid       | int(10) unsigned | NO   | PRI | NULL    | auto_increment |
| state     | varchar(32)      | NO   |     |         |                |
| admin     | tinyint(1)       | NO   | MUL | 1       |                |
| phase1    | tinyint(1)       | NO   |     | 0       |                |
| phase2    | tinyint(1)       | NO   |     | 0       |                |
| isdefault | tinyint(1)       | NO   | MUL | 0       |                |
| weight    | int(10) unsigned | NO   |     | 0       |                |
| isclosed  | tinyint(1)       | NO   |     | 0       |                |
+-----------+------------------+------+-----+---------+----------------+
select * from support_states;
+-----+----------+-------+--------+--------+-----------+--------+----------+
| sid | state    | admin | phase1 | phase2 | isdefault | weight | isclosed |
+-----+----------+-------+--------+--------+-----------+--------+----------+
|   1 | new      |     0 |      1 |      0 |         1 |      0 |        0 |
|   2 | active   |     1 |      0 |      1 |         0 |      1 |        0 |
|   3 | pending  |     1 |      0 |      1 |         0 |      2 |        0 |
|   4 | closed   |     1 |      0 |      1 |         0 |      3 |        1 |
|   5 | blocked  |     1 |      0 |      1 |         0 |      2 |        0 |
|   6 | inactive |     1 |      0 |      1 |         0 |      2 |        0 |
+-----+----------+-------+--------+--------+-----------+--------+----------+

describe support_priority;
+-----------+------------------+------+-----+---------+----------------+
| Field     | Type             | Null | Key | Default | Extra          |
+-----------+------------------+------+-----+---------+----------------+
| pid       | int(10) unsigned | NO   | PRI | NULL    | auto_increment |
| priority  | varchar(32)      | NO   |     |         |                |
| isdefault | tinyint(1)       | NO   | MUL | 0       |                |
| weight    | int(10) unsigned | NO   | MUL | 0       |                |
+-----------+------------------+------+-----+---------+----------------+
select * from support_priority;
+-----+----------+-----------+--------+
| pid | priority | isdefault | weight |
+-----+----------+-----------+--------+
|   1 | low      |         0 |      1 |
|   2 | normal   |         1 |      2 |
|   3 | high     |         0 |      3 |
|   4 | critical |         0 |      5 |
|   5 | lowest   |         0 |      0 |
|   6 | urgent   |         0 |      4 |
+-----+----------+-----------+--------+

describe support_assigned;
+-------+------------------+------+-----+---------+-------+
| Field | Type             | Null | Key | Default | Extra |
+-------+------------------+------+-----+---------+-------+
| nid   | int(10) unsigned | NO   | PRI | 0       |       |
| uid   | int(10) unsigned | NO   | PRI | 0       |       |
+-------+------------------+------+-----+---------+-------+

*/

/**
 * Drupal 6 support ticket source from database.
 *
 * @MigrateSource(
 *   id = "d6_support_ticket"
 * )
 */
class SupportTicket extends DrupalSqlBase implements SourceEntityInterface {

  /**
   * The join options between the node and the node_revisions table.
   */
  const JOIN_NODE_REVISION = 'n.vid = nr.vid';

  /**
   * The join options between the node and the support_ticket table.
   */
  const JOIN_TICKET = 'n.nid = st.nid';

  /**
   * The join options between the support_ticket and state table.
   */
  const JOIN_STATE = 'st.state = ss.sid';

  /**
   * The join options between the support_ticket and priority table.
   */
  const JOIN_PRIORITY = 'st.priority = sp.pid';

  /**
   * The default filter format.
   *
   * @var string
   */
  protected $filterDefaultFormat;

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Select support ticket / node in its last revision.
    $query = $this->select('node_revisions', 'nr')
      ->fields('n', array(
        'nid',
        'vid',
        'type',
        'language',
        'status',
        'created',
        'changed',
        'comment',
        'moderate',
        'tnid',
        'translate',
      ))
      ->fields('st', array(
        'nid',
        'message_id',
        'state',
        'priority',
        'client',
        'assigned',
      ))
      ->fields('nr', array(
        'vid',
        'title',
        'body',
        'teaser',
        'log',
        'timestamp',
        'format',
      ));
    $query->addField('n', 'uid', 'node_uid');
    $query->addField('nr', 'uid', 'revision_uid');
    $query->addField('ss', 'state', 'state_name');
    $query->addField('sp', 'priority', 'priority_name');
    $query->innerJoin('node', 'n', static::JOIN_NODE_REVISION);
    $query->innerJoin('support_ticket', 'st', static::JOIN_TICKET);
    $query->innerJoin('support_states', 'ss', static::JOIN_STATE);
    $query->innerJoin('support_priority', 'sp', static::JOIN_PRIORITY);
    // @todo: get client name from appropriate table

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $this->filterDefaultFormat = $this->variableGet('filter_default_format', '1');
    return parent::initializeIterator();
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = array(
      'nid' => $this->t('Support Ticket ID'),
      'type' => $this->t('Type'), // ?
      'title' => $this->t('Title'),
      'body' => $this->t('Body'),
      'format' => $this->t('Format'),
      'teaser' => $this->t('Teaser'),
      'node_uid' => $this->t('Ticket created by (uid)'),
      'revision_uid' => $this->t('Revision authored by (uid)'),
      'created' => $this->t('Created timestamp'),
      'changed' => $this->t('Modified timestamp'),
      'status' => $this->t('Published'),
      'revision' => $this->t('Create new revision'),
      'language' => $this->t('Language (fr, en, ...)'),
      'tnid' => $this->t('The translation set id for this node'),
      'timestamp' => $this->t('The timestamp the latest revision of this node was created.'),
      'state_name' => $this->t('The ticket state'),
      'priority_name' => $this->t('The ticket priority'),
      'message_id' => $this->t('The message_id if this update was created by email.'),
      'assigned' => $this->t('Who the ticket is assigned to (uid)'),
    );
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // format = 0 can happen when the body field is hidden. Set the format to 1
    // to avoid migration map issues (since the body field isn't used anyway).
    if ($row->getSourceProperty('format') === '0') {
      $row->setSourceProperty('format', $this->filterDefaultFormat);
    }
    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return array(
      'nid' => array(
        'type' => 'integer',
        'alias' => 'n',
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
