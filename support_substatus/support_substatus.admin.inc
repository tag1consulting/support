<?php

/**
 * Overview of client substatus fields.
 */
function support_substatus_admin_overview() {
  $rows = array();

  $header = array(
    array('data' => t('Substatus'), 'field' => 'ss.substatus'),
    array('data' => t('State(s)')),
    array('data' => t('Client(s)')),
    array('data' => t('Weight'), 'field'=> 'ss.weight'),
    array('data' => t('Disabled'), 'field'=> 'ss.disabled'),
    array('data' => t('Options')),
  );
  $sql = 'SELECT ss.ssid, ss.substatus, ss.weight, ss.disabled FROM {support_substatus} ss';
  $sql .= tablesort_sql($header);
  $result = pager_query($sql, 50, 0);
  while ($substatus = db_fetch_object($result)) {
    $options = l(t('edit'), "admin/support/substatus/$substatus->ssid/edit");

    $states = array();
    $result2 = db_query('SELECT sss.ssid, ss.state FROM {support_substatus_state} sss LEFT JOIN {support_states} ss ON sss.stid = ss.sid WHERE sss.ssid = %d', $substatus->ssid);
    while ($state = db_fetch_object($result2)) {
      $states[] = check_plain($state->state);
    }
    if (empty($states)) {
      $states[] = '<em>'. t('All states') .'</em>';
    }

    $clients = array();
    $result2 = db_query('SELECT ssc.clid, sc.name FROM {support_substatus_client} ssc LEFT JOIN {support_client} sc ON ssc.clid = sc.clid WHERE ssc.ssid = %d', $substatus->ssid);
    while ($client = db_fetch_object($result2)) {
      $clients[] = check_plain($client->name);
    }
    if (empty($clients)) {
      $clients[] = '<em>'. t('All clients') .'</em>';
    }

    $rows[] = array(
      truncate_utf8(check_plain($substatus->substatus), 52, TRUE, TRUE),
      implode(', ', $states),
      implode(', ', $clients),
      number_format($substatus->weight),
      $substatus->disabled ? t('Disabled') : t('Active'),
      $options,
    );
  }

  if (empty($rows)) {
    drupal_set_message(t('There are currently no substatus fields defined.'));
  }
  return theme('table', $header, $rows);
}

function support_substatus_admin_form(&$form_state, $substatus = array()) {
  $form = array();

  $form['substatus'] = array(
    '#title' => t('Substatus'),
    '#type' => 'textfield',
    '#required' => TRUE,
    '#maxlength' => 255,
    '#default_value' => isset($substatus->substatus) ? $substatus->substatus : '',
    '#description' => t('The Substatus field name. This name is visible to clients.'),
  );

  $options = _support_states();
  $form['state'] = array(
    '#title' => t('State'),
    '#type' => 'select',
    '#options' => $options,
    '#required' => TRUE,
    '#multiple' => TRUE,
    '#maxlength' => 255,
    '#size' => count($options),
    '#default_value' => isset($substatus->state) ? $substatus->state : '',
    '#description' => t('Specify which state(s) this substatus field applies to.'),
  );

  $form['disabled'] = array(
    '#title' => t('Disabled'),
    '#type' => 'checkbox',
    '#default_value' => isset($substatus->disabled) ? $substatus->disabled : 0,
    '#description' => t('Disabled substatus fields won\'t show up as an option when creating new tickets.'),
  );

  $form['weight'] = array(
    '#title' => t('Weight'),
    '#type' => 'weight',
    '#default_value' => isset($substatus->weight) ? $substatus->weight : 0,
    '#description' => t('When multiple substatus fields are available, the substatus field with the smallest (negative) weight will be selected as the default.'),
  );

  $clients = _support_clients_load();
  if (!isset($clients)) {
    drupal_set_message(t('You must !create a client before you can add substatus fields.', array('!create' => l(t('create and enable'), 'admin/support/clients/add'))), 'error');
    drupal_goto('admin/support/clients');
  }
  $form['clids'] = array(
    '#title' => t('Clients'),
    '#type' => 'select',
    '#options' => $clients,
    '#multiple' => TRUE,
    '#size' => count($clients) > 5 ? count($clients) : 5,
    '#default_value' => isset($substatus->clids) ? $substatus->clids : array(),
    '#description' => t('Select the client(s) this substatus field applies to.  Select no clients to have this substatus field apply to all clients.'),
  );

  $form['ssid'] = array(
    '#value' => $substatus->ssid,
    '#type' => 'hidden',
  );

  $form['submit'] = array(
    '#value' => isset($substatus->ssid) ? t('Update substatus field') : t('Add substatus field'),
    '#type' => 'submit',
  );
  if (isset($substatus->ssid)) {
    $form['delete'] = array(
      '#value' => t('Delete substatus field'),
      '#type' => 'submit',
    );
    $form['cancel'] = array(
      '#value' => l(t('Cancel'), 'admin/support/substatus'),
    );
  }

  return $form;
}

/**
 * Save new or updated substatus field definition.
 */
function support_substatus_admin_form_validate($form, &$form_state) {
  $path = $form_state['values']['path'];
  $ssid = isset($form_state['values']['ssid']) ? $form_state['values']['ssid'] : 0;
  if ($path != preg_replace('/[^0-9a-zA-Z_-]/', '', $path)) {
    form_set_error('path', t('Path %path contains invalid characters.', array('%path' => $path)));
  }
  if (strtolower($path) == 'null') {
    form_set_error('path', t('The path %path is reserved by the system, please choose a different path.', array('%path' => strtolower($path))));
  }
  if ($path && $ssid) {
    $ssid = db_result(db_query("SELECT ssid FROM {support_substatus} WHERE path = '%s' AND ssid != %d", $path, $form_state['values']['ssid']));
  }
  else if ($path) {
    $ssid = db_result(db_query("SELECT ssid FROM {support_substatus} WHERE path = '%s'", $path));
  }
  else {
    $ssid = NULL;
  }
  if ($ssid) {
    form_set_error('path', t('Path %path is already being used by another substatus field.', array('%path' => $path)));
  }
}

/**
 * Save new or updated substatus field definition.
 */
function support_substatus_admin_form_submit($form, &$form_state) {
  $client = support_client_load($form_state['values']['clid']);
  if ($form_state['clicked_button']['#value'] == t('Delete substatus field')) {
    drupal_set_message(t('Deleted %substatus substus field.', array('%substatus' => $form_state['values']['substatus'])));
    db_query('DELETE FROM {support_substatus} WHERE ssid = %d', $form_state['values']['ssid']);
    db_query('DELETE FROM {support_substatus_client} WHERE ssid = %d', $form_state['values']['ssid']);
    db_query('DELETE FROM {support_substatus_state} WHERE ssid = %d', $form_state['values']['ssid']);
    db_query('DELETE FROM {support_substatus_ticket} WHERE subid = %d', $form_state['values']['ssid']);
  }
  else if ($form_state['clicked_button']['#value'] == t('Update substatus field')) {
    db_query("UPDATE {support_substatus} SET substatus = '%s', disabled = %d, weight = %d WHERE ssid = %d", $form_state['values']['substatus'], $form_state['values']['disabled'], $form_state['values']['weight'], $form_state['values']['ssid']);
    db_query('DELETE FROM {support_substatus_client} WHERE ssid = %d', $form_state['values']['ssid']);
    foreach ($form_state['values']['clids'] as $clid) {
      db_query('INSERT INTO {support_substatus_client} (ssid, clid) VALUES(%d, %d)', $form_state['values']['ssid'], $clid);
    }
    db_query('DELETE FROM {support_substatus_state} WHERE ssid = %d', $form_state['values']['ssid']);
    foreach ($form_state['values']['state'] as $stid) {
      db_query('INSERT INTO {support_substatus_state} (ssid, stid) VALUES(%d, %d)', $form_state['values']['ssid'], $stid);
    }
    drupal_set_message(t('Updated %substatus field substatus.', array('%substatus' => $form_state['values']['substatus'])));
  }
  else {
    db_query("INSERT INTO {support_substatus} (substatus, disabled, weight) VALUES('%s', %d, %d)", $form_state['values']['substatus'], $form_state['values']['disabled'], $form_state['values']['weight']);
drupal_set_message("INSERT INTO {support_substatus} (substatus, disabled, weight) VALUES('". $form_state['values']['substatus'] ."', ". $form_state['values']['disabled'] .", ". $form_state['values']['weight'] .")");
    $ssid = db_last_insert_id('support_substatus', 'ssid');
    foreach ($form_state['values']['clids'] as $clid) {
      db_query('INSERT INTO {support_substatus_client} (ssid, clid) VALUES(%d, %d)', $ssid, $clid);
    }
    foreach ($form_state['values']['state'] as $stid) {
      db_query('INSERT INTO {support_substatus_state} (ssid, stid) VALUES(%d, %d)', $ssid, $stid);
    }
    drupal_set_message(t('Created %substatus substatus field.', array('%substatus' => $form_state['values']['substatus'])));
  }
  menu_rebuild();
  drupal_goto('admin/support/substatus');
}
