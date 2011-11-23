<?php

/**
 *
 */
function support_page_user($user, $assigned = FALSE, $state = NULL) {
  drupal_add_css(drupal_get_path('module', 'support') . '/support-tickets.css');
  if ($assigned) {
    drupal_set_title(t("@username's assigned tickets", array('@username' => $user->name)));
  }
  else {
    drupal_set_title(t("@username's latest tickets", array('@username' => $user->name)));
  }

  if (!$state) {
    $state = 'all open';
  }
  $state = _support_get_state($state);

  $sort = variable_get('support_default_sort_tickets', SUPPORT_SORT_UPDATE);
  if (variable_get('support_default_sort_order', SUPPORT_SORT_DESC) == SUPPORT_SORT_DESC) {
    $order = 'desc';
  }
  else {
    $order = 'asc';
  }
  foreach (array(
    SUPPORT_SORT_UPDATE => array('data' => t('Updated'), 'field' => 'last_updated'),
    SUPPORT_SORT_NID => array('data' => t('Id'), 'field' => 'n.nid'),
    SUPPORT_SORT_STATE => array('data' => t('State'), 'field' => 't.state'),
    SUPPORT_SORT_PRIORITY => array('data' => t('Priority'), 'field' => 't.priority')) as $key => $array) {
    if ($sort == $key) {
      $headers[$key] = $array + array('sort' => $order);
    }
    else {
      $headers[$key] = $array;
    }
  }

  $rows = array();
  $header = array(
    $headers[SUPPORT_SORT_NID],
    array('data' => t('Ticket'), 'field' => 'n.title'),
    $headers[SUPPORT_SORT_UPDATE],
    array('data' => t('Reported by'), 'field' => 'n.uid'),
    array('data' => t('Assigned to'), 'field' => 't.assigned'),
    $headers[SUPPORT_SORT_STATE],
    $headers[SUPPORT_SORT_PRIORITY],
    array('data' => t('Updates'), 'field' => 'l.comment_count'),
  );

  if ($state < 0) {
    $state = 'AND t.state NOT IN ('. implode(', ', _support_get_state(SUPPORT_STATE_CLOSED)) .')';
  }
  else {
    $state = $state ? "AND t.state = $state" : '';
  }

  if (_support_access_tickets() > 1) {
    $header[] = array('data' => t('Client'), 'field' => 't.client');
  }

  $clients = support_search_available_clients();
  if (sizeof($clients)) {
    $sql = "SELECT DISTINCT(n.nid), n.title, n.type, n.changed, n.uid, u.name, GREATEST(n.changed, l.last_comment_timestamp) AS last_updated, l.comment_count, t.state, t.priority, t.client, t.assigned FROM {node} n LEFT JOIN {support_ticket} t ON n.nid = t.nid INNER JOIN {node_comment_statistics} l ON n.nid = l.nid INNER JOIN {users} u ON n.uid = u.uid LEFT JOIN {comments} c ON n.nid = c.nid WHERE (c.status = ". COMMENT_PUBLISHED ." OR c.status IS NULL) AND n.status = 1 AND n.type = 'support_ticket' AND client IN (". implode(', ', $clients) .") $state";
    if ($assigned) {
      $sql .= " AND t.assigned = $user->uid";
    }
    if (!$assigned || (user_access('only view own tickets') && !user_access('administer support') && !user_access('edit any ticket') && !user_access('delete any ticket'))) {
      $sql .= " AND (n.uid = $user->uid OR t.assigned = $user->uid)";
    }
    $sql = db_rewrite_sql($sql);
    $sql .= tablesort_sql($header);
    switch (variable_get('support_secondary_sort_tickets', SUPPORT_SORT_NONE)) {
      case SUPPORT_SORT_UPDATE:
        $sql .= ", last_updated $order";
        break;
      case SUPPORT_SORT_NID:
        $sql .= ", n.nid $order";
        break;
      case SUPPORT_SORT_STATE:
        $sql .= ", t.state $order";
        break;
      case SUPPORT_SORT_PRIORITY:
        $sql .= ", t.priority $order";
        break;
    }
    $sql_count = "SELECT COUNT(DISTINCT(n.nid)) FROM {node} n LEFT JOIN {support_ticket} t ON n.nid = t.nid LEFT JOIN {comments} c ON n.nid = c.nid WHERE (c.status = ". COMMENT_PUBLISHED ." OR c.status IS NULL) AND n.status = 1 AND n.type = 'support_ticket' AND t.assigned = $user->uid $state";
    $sql_count = db_rewrite_sql($sql_count);
    $result = pager_query($sql, 50, 0, $sql_count);
  }
  $row = 0;
  $rows = array();
  while ($ticket = db_fetch_object($result)) {
    drupal_alter('support_page_list_ticket', $ticket);
    $account = user_load(array('uid' => $ticket->uid));
    $assigned = user_load(array('uid' => $ticket->assigned));
    $comments = l($ticket->comment_count, "node/$ticket->nid", array('fragment' => 'comments'));
    if ($new = comment_num_new($ticket->nid)) {
      $node = node_load($ticket->nid);
      $comments .= '&nbsp;('. l(format_plural($new, '1 new', '@count new'), "node/$ticket->nid", array('query' => comment_new_page_count($node->comment_count, $new, $node), 'fragment' => 'new')) .')';
    }
    $client = support_client_load($ticket->client);
    $rows[] = array('data' => array(
      array('data' => l($ticket->nid, "node/$ticket->nid"), 'class' => 'ticket-id'),
      array('data' => l(_support_truncate($ticket->title), "node/$ticket->nid"), 'class' => 'ticket-title'),
      array('data' => format_date($ticket->last_updated, 'small'), 'class' => 'ticket-updated'),
      array('data' => l(_support_truncate($account->name, 24), "user/$account->uid"), 'class' => 'ticket-reported'),
      array('data' => l(_support_truncate($assigned->name, 24), "user/$assigned->uid"), 'class' => 'ticket-assigned'),
      array('data' => check_plain(_support_state($ticket->state)), 'class' => 'ticket-state'),
      array('data' => check_plain(_support_priorities($ticket->priority)), 'class' => 'ticket-priority'),
      array('data' => $comments, 'class' => 'ticket-updates'),
    ), 'class' => "state-$ticket->state priority-$ticket->priority");
    if (_support_access_tickets() > 1) {
      $rows[$row]['data'][] = l($client->name, "support/$client->path");
    }
    $row++;
  }
  return theme('support_page_user', $header, $rows);
}

function theme_support_page_user($header, $rows) {
  return theme('table', $header, $rows, array('class' => 'support')) . theme('pager');
}
