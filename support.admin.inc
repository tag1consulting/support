<?php

/**
 * Administrative listing of all clients.
 */
function support_admin_client_overview() {
  $rows = array();
  $header = array(
    array('data' => t('Client name'), 'field' => 'name'),
    array('data' => t('Status'), 'field' => 'status'),
    array('data' => t('From address'), 'field' => 'mailfrom'));
  $sql = 'SELECT clid, name, status, mailfrom FROM {jobtrack_client}';
  $sql .= tablesort_sql($header);
  $result = pager_query($sql, 50, 0);
  while ($client = db_fetch_object($result)) {
    $rows[] = array(
      $client->name,
      $client->status ? t('enabled') : t('disabled'),
      $client->mailfrom,
      l(t('edit'), "admin/support/clients/$client->clid/edit") .' | '.
      l(t('fetch'), "admin/support/clients/$client->clid/fetch"));
  }
  if (empty($rows)) {
    drupal_set_message('There are currently no clients defined.');
  }
  return theme('table', $header, $rows);
}

/**
 * Administratively add/update a client.
 */
function support_admin_client(&$form_state, $client = NULL) {
  $form = array();

  $form['name'] = array(
    '#title' => t('Client name'),
    '#type' => 'textfield',
    '#required' => TRUE,
    '#default_value' => isset($client->name) ? $client->name : '',
  );
  $form['path'] = array(
    '#title' => t('Path'),
    '#type' => 'textfield',
    '#required' => TRUE,
    '#default_value' => isset($client->path) ? $client->path : '',
  );
  $form['server_name'] = array(
    '#title' => t('Server name'),
    '#type' => 'textfield',
    '#description' => t('Hostname or IP address.'),
    '#required' => TRUE,
    '#default_value' => isset($client->server_name) ? $client->server_name : '',
  );
  $form['server_username'] = array(
    '#title' => t('Server username'),
    '#type' => 'textfield',
    '#required' => TRUE,
    '#default_value' => isset($client->server_username) ? $client->server_username : '',
  );
  $form['server_password'] = array(
    '#title' => t('Server password'),
    '#type' => 'textfield',
    '#required' => TRUE,
    '#default_value' => isset($client->server_password) ? $client->server_password : '',
  );
  $form['mailbox'] = array(
    '#title' => t('Mailbox'),
    '#type' => 'textfield',
    '#required' => TRUE,
    '#default_value' => isset($client->mailbox) ? $client->mailbox : 'INBOX',
  );
  $form['mailfrom'] = array(
    '#title' => t('From address'),
    '#type' => 'textfield',
    '#required' => TRUE,
    '#default_value' => isset($client->mailfrom) ? $client->mailfrom : '',
  );
  $form['port'] = array(
    '#title' => t('Port'),
    '#type' => 'textfield',
    '#required' => TRUE,
    '#default_value' => isset($client->port) ? $client->port : '',
  );
  $form['protocol'] = array(
    '#type' => 'radios',
    '#title' => t('Protocol'),
    '#default_value' => 0,
    '#options' => array('pop3', 'secure pop3', 'imap', 'secure imap', 'local file'),
    '#required' => TRUE,
    '#description' => t('Select the protocol used to obtain messages from your mail server.'),
    '#default_value' => isset($client->protocol) ? $client->protocol : 0,
  );

  $form['status'] = array(
    '#title' => t('Status'),
    '#type' => 'radios',
    '#options' => array(1 => t('Enabled'), 2 => t('Disabled')),
    '#default_value' => isset($client->status) ? $client->status : 1,
  );
  $form['delete_read'] = array(
    '#title' => t('Delete messages from server after downloading'),
    '#type' => 'checkbox',
    '#default_value' => TRUE,
    '#default_value' => isset($client->delete_read) ? $client->delete_read : 1,
  );
  $form['autosubscribe'] = array(
    '#title' => t('Auto-subscribe'),
    '#type' => 'textfield',
    '#default_value' => isset($client->autosubscribe) ? $client->autosubscribe : '',
    '#description' => t('Enter one or more comma-seperated usernames to auto-subscribe to !client issues', array('!client' => $client->name)),
  );
  $form['domains'] = array(
    '#title' => t('Valid email domains'),
    '#type' => 'textfield',
    '#default_value' => isset($client->domains) ? $client->domains : '',
    '#description' => t('Enter one or more comma-seperated domains which are allowed to automatically generate new tickets.  If no domains are entered, tickets are allowed from all domains (unless you globally define allowed domains).'),
  );
  $form['notes'] = array(
    '#title' => t('Notes'),
    '#type' => 'textarea',
    '#default_value' => isset($client->notes) ? $client->notes : '',
  );
  $form['clid'] = array(
    '#type' => 'hidden',
    '#value' => $client->clid,
  );
  $form['submit'] = array(
    '#value' => isset($client->clid) ? t('Update client') : t('Add client'),
    '#type' => 'submit',
  );
  if (isset($client->clid)) {
    $form['delete'] = array(
      '#value' => t('Delete client'),
      '#type' => 'submit',
    );
    $form['cancel'] = array(
      '#value' => l(t('Cancel'), 'admin/support/clients'),
    );
  }

  
  return $form;
}

function support_admin_client_validate($form, &$form_state) {
  if (is_array($form['autosubscribe'] && !empty($form['autosubscribe']['#value']))) {
    $subscribe = array();
    $autosubscribe = explode(',', $form['autosubscribe']['#value']);
    foreach ($autosubscribe as $name) {
      $account = user_load(array('name' => trim($name)));
      if (!is_object($account) || !$account->uid) {
        form_set_error('autosubscribe', t('User !username does not exist.', array('!username' => $name)));
      }
    }
  }
  if (is_array($form['domains']) && !empty($form['domains']['#value'])) {
    _support_validate_domains('domains', explode(',', $form['domains']['#value']));
  }
}

/**
 * Add/update client information.
 */
function support_admin_client_submit($form, &$form_state) {
  if ($form_state['clicked_button']['#value'] == t('Delete client')) {
    drupal_set_message(t('Client %client deleted.', array('%client' => $form_state['values']['name'])));
    db_query('DELETE FROM {jobtrack_client} WHERE clid = %d', $form_state['values']['clid']);
  }
  else {
    db_query("UPDATE {jobtrack_client} SET name = '%s', path = '%s', status = %d, server_name = '%s', server_username = '%s', server_password = '%s', mailbox = '%s', mailfrom = '%s', protocol = %d, port = %d, delete_read = %d, autosubscribe = '%s', domains = '%s', notes = '%s' WHERE clid = %d", $form_state['values']['name'], $form_state['values']['path'], $form_state['values']['status'], $form_state['values']['server_name'], $form_state['values']['server_username'], $form_state['values']['server_password'], $form_state['values']['mailbox'], $form_state['values']['mailfrom'], $form_state['values']['protocol'], $form_state['values']['port'], $form_state['values']['delete_read'], $form_state['values']['autosubscribe'], $form_state['values']['domains'], $form_state['values']['notes'], $form_state['values']['clid']);
    if (!db_affected_rows()) {
      drupal_set_message(t('Client %client added.', array('%client' => $form_state['values']['name'])));
      db_query("INSERT INTO {jobtrack_client} (name, path, status, server_name, server_username, server_password, mailbox, mailfrom, protocol, port, delete_read, autosubscribe, domains, notes) VALUES('%s', '%s', %d, '%s', '%s', '%s', '%s', '%s', %d, %d, %d, '%s', '%s', '%s')", $form_state['values']['name'], $form_state['values']['path'], $form_state['values']['status'], $form_state['values']['server_name'], $form_state['values']['server_username'], $form_state['values']['server_password'], $form_state['values']['mailbox'], $form_state['values']['mailfrom'], $form_state['values']['protocol'], $form_state['values']['port'], $form_state['values']['delete_read'], $form_state['values']['autosubscribe'], $form_state['values']['domains'], $form_state['values']['notes']);
    }
    else {
      drupal_set_message(t('Client %client updated.', array('%client' => $form_state['values']['name'])));
    }
  }
  // clean page cache and block cache
  cache_clear_all();
  // clean menu cache
  cache_clear_all(NULL, 'cache_menu');
  drupal_goto('admin/support/clients');
}

/**
 * Admin page block.
 */
function support_admin_menu_block_page() {
  $item = menu_get_item();
  if ($content = system_admin_menu_block($item)) {
    $output = theme('admin_block_content', $content);
  }
  else {
    $output = t('You do not have any administrative items.');
  }
  return $output;
}


function support_admin_settings() {
  $form = array();

  $form['support_global_domains'] = array(
    '#type' => 'textfield',
    '#title' => t('Allowed domains'),
    '#description' => t('Optionally enter one or more domains which are allowed to automatically create tickets by sending an email.  If no domains are entered, all domains are allowed.  If entering multiple domains, separate them by commas.  These domains will be allowed for all clients.'),
    '#default_value' => variable_get('support_global_domains', ''),
  );

  return system_settings_form($form);
}

/**
 * Be sure valid domains are entered.
 */
function support_admin_settings_validate($form, &$form_state) {
  if (!empty($form['support_global_domains']['#value'])) {
    _support_validate_domains('support_global_domains', explode(',', trim($form['support_global_domains']['#value'])));
  }
}

/**
 * Validate domains by doing a dns lookup on each.
 */
function _support_validate_domains($field, $domains = array()) {
  foreach ($domains as $domain) {
    if ($domain && !checkdnsrr(trim($domain))) {
      form_set_error($field, t('Invalid domain: %domain', array('%domain' => $domain)));
    }
  }
}
