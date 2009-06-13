<?php
// $Id$

/**
 * @file support.admin.inc
 */

/**
 * Administrative listing of all clients.
 */
function support_admin_client_overview() {
  $rows = array();
  $header = array(
    array('data' => t('Client name'), 'field' => 'jc.name'),
    array('data' => t('Status'), 'field' => 'jc.status'),
    array('data' => t('Inbound email'), 'field' => 'jc.integrate_email'),
    array('data' => t('From address'), 'field' => 'jc.mailfrom'),
    array('data' => t('Options')));
  $sql = 'SELECT clid, name, status, integrate_email, mailfrom FROM {support_client} jc';
  $sql .= tablesort_sql($header);
  $result = pager_query($sql, 50, 0);
  while ($client = db_fetch_object($result)) {
    $options = l(t('edit'), "admin/support/clients/$client->clid/edit");
    if ($client->integrate_email) {
      $options .= ' | '. l(t('fetch'), "admin/support/clients/$client->clid/fetch");
    }
    $rows[] = array(
      check_plain($client->name),
      $client->status == 1 ? t('enabled') : t('disabled'),
      $client->integrate_email ? t('integrated') : t('not integrated'),
      $client->integrate_email ? check_plain($client->mailfrom) : '<em>'. t('using default') .'</em>: '. l(_support_admin_default_mail(), 'admin/support/settings'),
      $options);
  }
  if (empty($rows)) {
    drupal_set_message('There are currently no clients defined.');
  }
  return theme('table', $header, $rows);
}

function _support_admin_default_mail() {
  $mail = variable_get('support_global_mailfrom', '');
  if (empty($mail)) {
    $mail = variable_get('site_mail', ini_get('sendmail_from'));
  }
  return $mail;
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
    '#default_value' => isset($client->name) ? check_plain($client->name) : '',
    '#description' => t('The full name of your client.  This name will be used when building menus, sending emails, and anywhere else the name of your client is visibly displayed.'),
  );
  $form['path'] = array(
    '#title' => t('Path'),
    '#type' => 'textfield',
    '#required' => TRUE,
    '#default_value' => isset($client->path) ? check_plain($client->path) : '',
    '#description' => t("Used to build the path to this client\s tickets.  The path should only be comprised of alphanumeric characters, dashes, and underscores."),
  );
  $form['status'] = array(
    '#title' => t('Status'),
    '#type' => 'radios',
    '#options' => array(1 => t('Enabled'), 2 => t('Disabled')),
    '#default_value' => isset($client->status) ? $client->status : 1,
    '#description' => t('Tickets will only be displayed for enabled clients.'),
  );
  $form['mail'] = array(
    '#title' => t('Inbound email integration'),
    '#type' => 'fieldset',
    '#collapsible' => TRUE,
    '#collapsed' => isset($client->integrate_email) && $client->integrate_email == TRUE ? FALSE : TRUE,
  );
  $form['mail']['integrate_email'] = array(
    '#title' => t('Integrate inbound email'),
    '#type' => 'checkbox',
    '#default_value' => isset($client->integrate_email) && $client->integrate_email == TRUE ? 1 : 0,
    '#description' => t('Check this box if you would like to fully integrate this client with an email server.  This will allow users to create and update tickets by email, but it requires a more complex configuration as you need to create a unique mailbox for each integrated client.  If you do not check this box, email notifications will still be mailed out as tickets are created and updated, but replies to these tickets will be ignored.') .' <em>'. t('Inbound email integration requires that your version of PHP was !compiled.', array('!compiled' => l(t('compiled with the IMAP extension'), 'http://php.net/imap'))) .'</em>',
  );
  $form['mail']['server_name'] = array(
    '#title' => t('Server name'),
    '#type' => 'textfield',
    '#description' => t('Hostname or IP address.'),
    '#default_value' => isset($client->server_name) ? check_plain($client->server_name) : '',
    '#description' => t("The hostname or IP address of the mail server where your client's dedicated email address is hosted.  For example, client %foo may receive email at %email, and so the server name may be %hostname.  The server name may also be an IP address, such as %ip.", array('%foo' => 'foo', '%email' => 'foo@support.sample.com', '%hostname' => 'support.sample.com', '%ip' => '192.168.1.1')),
  );
  $form['mail']['server_username'] = array(
    '#title' => t('Server username'),
    '#type' => 'textfield',
    '#default_value' => isset($client->server_username) ? check_plain($client->server_username) : '',
    '#description' => t("The client's email username.  If a client receives email at %email, the server username is often simply %user.  The support module uses this username to log into the mail server to download messages.", array('%email' => 'foo@support.sample.com', '%user' => 'foo')),
  );
  $form['mail']['server_password'] = array(
    '#title' => t('Server password'),
    '#type' => 'textfield',
    '#default_value' => isset($client->server_password) ? check_plain($client->server_password) : '',
    '#description' => t("The client's email password.  The support module uses this password to log into the mail server to download messages. %notice", array('%notice' => t('Note: this is not a password you request from your client.  This is a password you configure on your mail server for managing support tickets for a client.'))),
  );
  $form['mail']['mailbox'] = array(
    '#title' => t('Mailbox'),
    '#type' => 'textfield',
    '#default_value' => isset($client->mailbox) ? check_plain($client->mailbox) : 'INBOX',
    '#description' => t("The mailbox path on your mail server.  You generally do not need to modify this setting."),
  );
  $form['mail']['mailfrom'] = array(
    '#title' => t('From address'),
    '#type' => 'textfield',
    '#default_value' => isset($client->mailfrom) ? check_plain($client->mailfrom) : '',
    '#description' => t('The email address support should use when sending notification emails.  This must be the same client-specific email address users will send messages to when creating and updating tickets via email.  This will often be comprised of your server username and your server name, such as %email.', array('%email' => 'foo@support.sample.com')),
  );
  $form['mail']['port'] = array(
    '#title' => t('Port'),
    '#type' => 'textfield',
    '#default_value' => isset($client->port) ? check_plain($client->port) : '',
    '#description' => t('Specify the TCP port to connect to when downloading email messages.  If using pop3, this port is normally 110.  If using secure pop3, this port is normally 995.  If using imap, this port is normally 220.  If using secure imap this port is normally 993.'),
  );
  $form['mail']['protocol'] = array(
    '#type' => 'radios',
    '#title' => t('Protocol'),
    '#default_value' => 0,
    '#options' => array('pop3', 'secure pop3', 'imap', 'secure imap', 'local file'),
    '#description' => t('Select the protocol used to obtain messages from your mail server.'),
    '#default_value' => isset($client->protocol) ? $client->protocol : 0,
  );
  $form['mail']['extra'] = array(
    '#title' => t('Optional extra flags'),
    '#type' => 'textfield',
    '#default_value' => isset($client->extra) ? check_plain($client->extra) : '/novalidate-cert',
    '#description' => t('The %pop3, %imap and %ssl flags will be automatically set for you depending on your configuration above.  Additional flags can be manually specified here.  If you are having trouble downloading messages, you may need to change these extra flags as !defined.  These flags are ignored when using the %local protocol.', array('!defined' => l(t('defined here'), 'http://php.net/imap_open#function.imap-open.parameters'), '%local' => t('local file'), '%pop3' => t('/pop3'), '%imap' => t('/imap'), '%ssl' => t('/ssl'))),
  );

  $form['mail']['domains'] = array(
    '#title' => t('Valid email domains'),
    '#type' => 'textfield',
    '#default_value' => isset($client->domains) ? check_plain($client->domains) : '',
    '#description' => t('Enter one or more comma-seperated domains which are allowed to automatically generate new tickets.  If no domains are entered, tickets are allowed from all domains (unless you globally define allowed domains).  If you globally define allowed domains but want this client to receive emails from all domains, enter a domain of "*".'),
  );
  $form['autosubscribe'] = array(
    '#type' => 'textfield',
    '#title' => t('Auto-subscribe'),
    '#autocomplete_path' => 'support/autocomplete/autosubscribe/'. $client->clid,
    '#default_value' => isset($client->autosubscribe) ? $client->autosubscribe : '',
    '#description' => t('Enter one or more comma-seperated usernames to auto-subscribe to !client issues.  These users will receive email notifications for all new tickets and ticket updates for this client.', array('!client' => $client->name)),
  );
  $form['notes'] = array(
    '#title' => t('Notes'),
    '#type' => 'textarea',
    '#default_value' => isset($client->notes) ? check_plain($client->notes) : '',
    '#description' => t('Notes are only displayed on this page when editing clients.  They are optional and are provided for administrative purposes.'),
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
  if (!empty($form_state['values']['autosubscribe'])) {
    $subscribe = array();
    $autosubscribe = explode(',', $form_state['values']['autosubscribe']);
    foreach ($autosubscribe as $name) {
      $account = user_load(array('name' => trim($name)));
      if (!is_object($account) || !$account->uid) {
        form_set_error('autosubscribe', t('User !username does not exist.', array('!username' => $name)));
      }
    }
  }
  if (!empty($form_state['values']['domains'])) {
    _support_validate_domains('domains', explode(',', $form['domains']['#value']));
  }
  if ($form_state['values']['integrate_email'] && !function_exists('imap_open')) {
    form_set_error('integrate_email', t('PHP must be !compiled in order to enable inbound email integration.  The IMAP functions are required to support the IMAP, POP3, and local mailbox access methods.', array('!compiled' => l(t('compiled with the IMAP extension'), 'http://php.net/imap'))));
  }
}

/**
 * Add/update client information.
 */
function support_admin_client_submit($form, &$form_state) {
  if ($form_state['clicked_button']['#value'] == t('Delete client')) {
    drupal_set_message(t('Client %client deleted.', array('%client' => $form_state['values']['name'])));
    db_query('DELETE FROM {support_client} WHERE clid = %d', $form_state['values']['clid']);
  }
  else {
    db_query("UPDATE {support_client} SET name = '%s', path = '%s', status = %d, integrate_email = %d, server_name = '%s', server_username = '%s', server_password = '%s', mailbox = '%s', mailfrom = '%s', protocol = %d, extra = '%s', port = %d, autosubscribe = '%s', domains = '%s', notes = '%s' WHERE clid = %d", check_plain($form_state['values']['name']), $form_state['values']['path'], $form_state['values']['status'], $form_state['values']['integrate_email'], $form_state['values']['server_name'], $form_state['values']['server_username'], $form_state['values']['server_password'], $form_state['values']['mailbox'], $form_state['values']['mailfrom'], $form_state['values']['protocol'], $form_state['values']['extra'], $form_state['values']['port'], $form_state['values']['autosubscribe'], $form_state['values']['domains'], $form_state['values']['notes'], $form_state['values']['clid']);
    if (!db_affected_rows()) {
      drupal_set_message(t('Client %client added.', array('%client' => $form_state['values']['name'])));
      db_query("INSERT INTO {support_client} (name, path, status, integrate_email, server_name, server_username, server_password, mailbox, mailfrom, protocol, extra, port, autosubscribe, domains, notes) VALUES('%s', '%s', %d, %d, '%s', '%s', '%s', '%s', '%s', %d, '%s', %d, %d, '%s', '%s')", check_plain($form_state['values']['name']), $form_state['values']['path'], $form_state['values']['status'], $form_state['values']['integrate_email'], $form_state['values']['server_name'], $form_state['values']['server_username'], $form_state['values']['server_password'], $form_state['values']['mailbox'], $form_state['values']['mailfrom'], $form_state['values']['protocol'], $form_state['values']['extra'], $form_state['values']['port'], $form_state['values']['autosubscribe'], $form_state['values']['domains'], $form_state['values']['notes']);
    }
    else {
      drupal_set_message(t('Client %client updated.', array('%client' => $form_state['values']['name'])));
    }
  }
  // clean page cache and block cache
  cache_clear_all();
  // rebuild the menu
  menu_rebuild();
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

  $form['mail'] = array(
    '#type' => 'fieldset',
    '#title' => t('Mail'),
  );
  $form['mail']['support_cron_download_mail'] = array(
    '#type' => 'checkbox',
    '#title' => t('Download mail via cron'),
    '#description' => t('When checked, support will try to download client emails each time cron is run.  You may want to disable this if you are not integrating support with email, or if you only wish to download mail manually or via the !path.  If automating the download of mail through the support fetch mail path, be sure to !assign permissions to the necessary role(s).', array('!path' => l(t('support fetch mail path'), 'support/fetch'), '!assign' => l(t('assign "download mail via support/fetch"'), 'admin/user/permissions', array('fragment' => 'module-support')))),
    '#default_value' => variable_get('support_cron_download_mail', TRUE),
  );
  $form['mail']['support_global_domains'] = array(
    '#type' => 'textfield',
    '#title' => t('Globally allowed domains'),
    '#description' => t('Optionally enter one or more domains which are allowed to automatically create tickets by sending an email.  If no domains are entered, or you specify "*", all domains are allowed.  If entering multiple domains, separate them by commas.  These domains will be allowed for all clients.'),
    '#default_value' => check_plain(variable_get('support_global_domains', '')),
  );
  $form['mail']['support_global_mailfrom'] = array(
    '#type' => 'textfield',
    '#title' => t('Default mail from address'),
    '#description' => t('Optionally specify a default <em>from</em> address where support notifications will originate from for clients whose inbound email is not integrated.  If a client\'s inbound email is integrated, you will need to specify the <em>from</em> address on the client\'s configuration page as each client gets a unique <em>from</em> address to allow support to process replies to tickets via email.'),
    '#default_value' => _support_admin_default_mail(),
  );

  $form['search'] = array(
    '#type' => 'fieldset',
    '#title' => t('Search'),
  );
  $form['search']['support_remove_tickets'] = array(
    '#type' => 'checkbox',
    '#title' => t('Remove tickets from content search results'),
    '#description' => t('When checked, support will remove tickets from content search results, and tickets can only be searched in their own search tab.  This is necessary to enforce proper access control, only letting a user\'s search return tickets that they have access to.'),
    '#default_value' => variable_get('support_remove_tickets', TRUE),
  );
  $form['search']['support_override_theme'] = array(
    '#type' => 'checkbox',
    '#title' => t('Override theme search form'),
    '#description' => t('When checked, support will override the theme search box so that it searches tickets.  When not checked, the theme search box will work normally.'),
    '#default_value' => variable_get('support_override_theme', FALSE),
  );
  $form['search']['support_override_block'] = array(
    '#type' => 'checkbox',
    '#title' => t('Override block search form'),
    '#description' => t('When checked, support will override the default search block so that it searches tickets.  When not checked, the block search form will work normally.'),
    '#default_value' => variable_get('support_override_block', FALSE),
  );

  $form['general'] = array(
    '#type' => 'fieldset',
    '#title' => t('General'),
  );
  $form['general']['support_autocomplete_limit'] = array(
    '#type' => 'select',
    '#title' => t('Autocomplete limit'),
    '#description' => t('A threshold above which select fields and checkboxes will turn into autocomplete text fields.  For example, if set to 20 and a ticket can be assigned to 25 users, the assigned widget will be a textfield requiring you to type the user name.  If set to 20 and a ticket can only be assigned to 10 users, the assigned widget will be a drop down select menu listing all matching users.'),
    '#options' => array(0 => t('always')) + drupal_map_assoc(array(5, 10, 15, 20, 25, 50, 75, 100, 250, 500, 750, 1000)),
    '#default_value' => variable_get('support_autocomplete_limit', 15),
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
  if (!empty($form['support_global_mailfrom']['#value']) && !valid_email_address($form['support_global_mailfrom']['#value'])) {
    form_set_error('support_global_mailfrom', t('You must enter a valid email address or leave the default mail from address field blank.'));
  }
}

/**
 * Validate domains by doing a dns lookup on each.
 */
function _support_validate_domains($field, $domains = array()) {
  foreach ($domains as $domain) {
    $domain = trim($domain);
    if ($domain && ($domain != '*' && !checkdnsrr($domain))) {
      form_set_error($field, t('Invalid domain: %domain', array('%domain' => $domain)));
    }
  }
}
