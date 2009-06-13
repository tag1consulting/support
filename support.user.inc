<?php

/**
 *
 */
function support_page_user($user, $assigned = FALSE) {
  if ($assigned) {
    drupal_set_title(t("@username's assigned tickets", array('@username' => $user->name)));
  }
  else {
    drupal_set_title(t("@username's latest tickets", array('@username' => $user->name)));
  }
  $rows = array();
  $header = array(
    array('data' => t('Ticket'), 'field' => 'n.title'),
    array('data' => t('Updated'), 'field' => 'last_updated', 'sort' => 'desc'),
    array('data' => t('Reporter'), 'field' => 'n.uid'),
    array('data' => t('State'), 'field' => 't.state'),
    array('data' => t('Prioriy'), 'field' => 't.priority'),
    array('data' => t('Updates'), 'field' => 'l.comment_count'),
  );

  if (_support_access_tickets() > 1) {
    $header[] = array('data' => t('Client'), 'field' => 't.client');
  }

  $sql = "SELECT DISTINCT(n.nid), n.title, n.type, n.changed, n.uid, u.name, GREATEST(n.changed, l.last_comment_timestamp) AS last_updated, l.comment_count, t.state, t.priority, t.client FROM {node} n LEFT JOIN {jobtrack_ticket} t ON n.nid = t.nid INNER JOIN {node_comment_statistics} l ON n.nid = l.nid INNER JOIN {users} u ON n.uid = u.uid LEFT JOIN {comments} c ON n.nid = c.nid WHERE (c.status = ". COMMENT_PUBLISHED ." OR c.status IS NULL) AND n.status = 1 AND (n.uid = $user->uid OR c.uid = $user->uid)";
  if ($assigned) {
    $sql .= " AND t.assigned = $user->uid";
  }
  $sql = db_rewrite_sql($sql);
  $sql_count = 'SELECT COUNT(DISTINCT(n.nid)) FROM {node} n LEFT JOIN {comments} c ON n.nid = c.nid AND (c.status = %d OR c.status IS NULL) WHERE n.status = 1 AND (n.uid = %d OR c.uid = %d)';
  $sql_count = db_rewrite_sql($sql_count);

  $sql .= tablesort_sql($header);
  $result = pager_query($sql, 50, 0, $sql_count);
  $row = 0;
  $rows = array();
  while ($ticket = db_fetch_object($result)) {
    $account = user_load(array('uid' => $ticket->uid));
    $comments = l($ticket->comment_count, "node/$ticket->nid", array('fragment' => 'comments'));
    if ($new = comment_num_new($ticket->nid)) {
      $node = node_load($ticket->nid);
      $comments .= '&nbsp;('. l(format_plural($new, '1 new', '@count new'), "node/$ticket->nid", array('query' => comment_new_page_count($node->comment_count, $new, $node), 'fragment' => 'new')) .')';
    }
    $client = support_client_load($ticket->client);
    $rows[] = array(
      l($ticket->title, "node/$ticket->nid"),
      format_date($ticket->changed, 'small'),
      l($account->name, "user/$account->uid"),
      _support_state($ticket->state),
      _support_priorities($ticket->priority),
      $comments
    );
    if (_support_access_tickets() > 1) {
      $rows[$row][] = l($client->name, "support/$client->path");
    }
    $row++;
  }
  return theme('table', $header, $rows);
}
