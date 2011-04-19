<?php

/**
 *
 */
function support_page_user($user, $assigned = FALSE, $state = NULL) {
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

  $rows = array();
  $header = array(
    array(
      'data' => t('Id'),
      'field' => 'n.nid',
      'class' => array('id'),
    ),
    array(
      'data' => t('Ticket'),
      'field' => 'n.title',
    ),
    array(
      'data' => t('Updated'),
      'field' => 'last_updated',
      'sort' => 'desc',
    ),
    array(
      'data' => t('Reported by'),
      'field' => 'n.uid',
    ),
    array(
      'data' => t('State'),
      'field' => 't.state',
    ),
    array(
      'data' => t('Priority'),
      'field' => 't.priority',
    ),
    array(
      'data' => t('Updates'),
      'field' => 'l.comment_count',
    ),
  );

  if (_support_access_tickets() > 1) {
    $header[] = array(
      'data' => t('Client'),
      'field' => 't.client',
    );
  }

  $clients = support_search_available_clients();

  if (sizeof($clients)) {
    $query = db_select('node', 'n')->extend('PagerDefault');
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

    if (!$assigned || (user_access('only view own tickets') && !user_access('administer support') && !user_access('edit any ticket') && !user_access('delete any ticket'))) {
      $query->condition('n.uid', $user->uid);
    }

    switch (variable_get('support_secondary_sort_order', 0)) {
      case 0:
        $order = 'ASC';
        break;
      case 1:
        $order = 'DESC';
        break;
    }
    switch (variable_get('support_secondary_sort_tickets', 0)) {
      case 1:
        $query->orderBy('last_updated', $order);
        break;
      case 2:
        $query->orderBy('n.nid', $order);
        break;
      case 3:
        $query->orderBy('t.state', $order);
        break;
      case 4:
        $query->orderBy('t.priority', $order);
        break;
    }

    $query->fields('n', array('nid', ' title', 'type', 'changed', 'uid'))
      ->fields('u', array('name'))
      ->fields('s', array('comment_count'))
      ->fields('t', array('client', ' state', 'priority', 'assigned'))
      ->distinct()
      ->addExpression('GREATEST(n.changed, s.last_comment_timestamp)', 'last_updated');

    $result = $query->execute();
  }
  $row = 0;
  $rows = array();
  foreach ($result as $ticket) {
    drupal_alter('support_page_list_ticket', $ticket);
    $account = user_load($ticket->uid);
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
          'data' => _support_state($ticket->state),
          'class' => array('ticket-state'),
        ),
        array(
          'data' => _support_priorities($ticket->priority),
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
      $rows[$row]['data'][] = l($client->name, "support/$client->path");
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
