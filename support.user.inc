<?php

/**
 *
 */
function support_page_user($user, $assigned = FALSE, $state = NULL) {
  $result = array();
  drupal_add_css(drupal_get_path('module', 'support') . '/support-tickets.css');
  if ($assigned) {
    drupal_set_title(t("@username's assigned tickets", array('@username' => $user->name)), PASS_THROUGH);
  }
  else {
    drupal_set_title(t("@username's latest tickets", array('@username' => $user->name)), PASS_THROUGH);
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
    array('data' => t('Updates'), 'field' => 's.comment_count'),
  );

  if (_support_access_tickets() > 1) {
    $header[] = array(
      'data' => t('Client'),
      'field' => 't.client',
    );
  }

  $clients = support_search_available_clients();

  if (sizeof($clients)) {
    $query = db_select('node', 'n')->extend('PagerDefault')->extend('TableSort');
    $query->leftjoin('support_ticket', 't', 't.nid = n.nid');
    $query->join('node_comment_statistics', 's', 's.nid = n.nid');
    $query->join('users', 'u', 'u.uid = n.uid');
    $query->leftjoin('comment', 'c', 'c.nid = n.nid');
    $query->condition(db_or()
      ->condition('c.status', COMMENT_PUBLISHED)
      ->isNull('c.status'))
      ->condition('n.status', NODE_PUBLISHED)
      ->condition('n.type', 'support_ticket')
      ->condition('t.client', $clients);

    if ($state < 0) {
      $states = _support_get_state(SUPPORT_STATE_CLOSED);
      $query->condition('t.state', $states, 'NOT IN');
    }
    else if ($state) {
      $query->condition('t.state', $state);
    }

    if ($assigned) {
      $query->condition('t.assigned', $user->uid);
    }

    if (!$assigned || (user_access('only view own tickets') && !user_access('administer support') && !user_access('edit any support_ticket content') && !user_access('delete any support_ticket content'))) {
      $query->condition(db_or()
        ->condition('n.uid', $user->uid)
        ->condition('t.assigned', $user->uid));
    }

    $query->fields('n', array('nid', 'title', 'type', 'changed', 'uid'))
      ->fields('u', array('name'))
      ->fields('s', array('comment_count'))
      ->fields('t', array('client', 'state', 'priority', 'assigned'))
      ->orderByHeader($header)
      ->distinct()
      ->addExpression('GREATEST(n.changed, s.last_comment_timestamp)', 'last_updated');

    switch (variable_get('support_secondary_sort_tickets', SUPPORT_SORT_NONE)) {
      case SUPPORT_SORT_UPDATE:
        $query->orderBy('last_updated', $order);
        break;
      case SUPPORT_SORT_NID:
        $query->orderBy('n.nid', $order);
        break;
      case SUPPORT_SORT_STATE:
        $query->orderBy('t.state', $order);
        break;
      case SUPPORT_SORT_PRIORITY:
        $query->orderBy('t.priority', $order);
        break;
    }

    $result = $query->execute();
  }
  $row = 0;
  $rows = array();
  foreach ($result as $ticket) {
    drupal_alter('support_page_list_ticket', $ticket);
    $account = user_load($ticket->uid);
    $assigned = user_load($ticket->assigned);
    $comments = l($ticket->comment_count, "node/$ticket->nid", array('fragment' => 'comments'));
    if ($new = comment_num_new($ticket->nid)) {
      $node = node_load($ticket->nid);
      $comments .= '&nbsp;(' . l(format_plural($new, '1 new', '@count new'), "node/$ticket->nid", array('query' => comment_new_page_count($node->comment_count, $new, $node), 'fragment' => 'new')) . ')';
    }
    $client = support_client_load($ticket->client);
    $rows[] = array(
      'data' => array(
        array(
          'data' => l($ticket->nid, "node/$ticket->nid"),
          'class' => array('ticket-id'),
        ),
        array(
          'data' => l(_support_truncate($ticket->title), "node/$ticket->nid"),
          'class' => array('ticket-title'),
        ),
        array(
          'data' => format_date($ticket->last_updated, 'short'),
          'class' => array('ticket-updated'),
        ),
        array(
          'data' => l(_support_truncate($account->name, 24), "user/$account->uid"),
          'class' => array('ticket-reported'),
        ),
        array(
          'data' => l(_support_truncate($assigned->name, 24), "user/$assigned->uid"),
          'class' => array('ticket-assigned'),
        ),
        array(
          'data' => check_plain(_support_state($ticket->state)),
          'class' => array('ticket-state'),
        ),
        array(
          'data' => check_plain(_support_priorities($ticket->priority)),
          'class' => array('ticket-priority'),
        ),
        array(
          'data' => $comments,
          'class' => array('ticket-updates'),
        ),
      ),
      'class' => array("state-$ticket->state priority-$ticket->priority"),
    );
    if (_support_access_tickets() > 1) {
      $rows[$row]['data'][] = l($client->name, support_queue_url($client));
    }
    $row++;
  }

  if (count($rows) == 0)
    $rows[] = array(array(
      'data' => t('No tickets available.'),
      'colspan' => count($header),
    ));

  return theme('support_page_user', array('header' => $header, 'rows' => $rows, 'attributes' => array('class' => array('support'))));
}

/**
 * @TODO Please document this function.
 * @see http://drupal.org/node/1354
 */
function theme_support_page_user($variables) {
  $header = $variables['header'];
  $rows = $variables['rows'];
  return theme('table', array('header' => $header, 'rows' => $rows, 'attributes' => array('class' => array('support')))) . theme('pager');
}
