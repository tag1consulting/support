<?php

/**
 * @file support.admin.inc
 */

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
function support_client_form($form, &$form_state, $client, $op = 'edit') {
  // During initial form build, add the entity to the form state for use
  // during form building and processing. During a rebuild, use what is in the
  // form state.
  $client = $form_state['support_client'];
  // @@@ These defaults should go in a custom entity class...
  $defaults = array(
    'name' => '',
    'path' => '',
    'status' => 1,
    'parent' => 0,
    'integrate_email' => 0, // correct default?
    'server_name' => '',
    'server_username' => '',
    'server_password' => '',
    'mailbox' => 'INBOX',
    'mailfrom' => '',
    'port' => '',
    'protocol' => 0,
    'extra' => '/novalidate-cert',
    'thread_subject' => 0,
    'domains' => '*',
    'user_creation' => 0,
    'autosubscribe' => '',
    'autoassign' => '',
    'notes' => '',
  );
  foreach ($defaults as $key => $value) {
    if (!isset($client->$key)) {
      $client->$key = $value;
    }
  }
  $form_state['support_client'] = $client;

  // Only enable if we end up needing this due to an api limitation...
  // $form['#client'] = $form_state['client'];

  $form['name'] = array(
    '#title' => t('Client name'),
    '#type' => 'textfield',
    '#required' => TRUE,
    '#default_value' => $client->name,
    '#description' => t('A unique name to be displayed throughout the website and in emails.'),
  );
  // NOTE: This is actually 'path' but calling it that here confuses pathauto. We fix it up in the submit function.
  $form['client_path'] = array(
    '#title' => t('Machine-readable name'),
    '#type' => 'machine_name',
    '#required' => TRUE,
    '#default_value' => $client->path,
    '#size' => 15,
    '#description' => t('The machine-readable name is used to build a unqiue URI for accessing client tickets. It must contain only letters, numbers, and underscores.'),
    '#machine_name' => array(
      'exists' => '_support_client_path_exists',
      'label' => t('Path'),
      'standalone' => TRUE,
    ),
  );

  $form['status'] = array(
    '#title' => t('Status'),
    '#type' => 'radios',
    '#options' => array(
      1 => t('Enabled'),
      2 => t('Disabled'),
    ),
    '#default_value' => $client->status,
    '#description' => t('Tickets will only be displayed for enabled clients.'),
  );
  $query = new EntityFieldQuery;
  $query
    ->entityCondition('entity_type', 'support_client')
    ->entityCondition('status', 1)
    ->entityCondition('parent', 0);
  // Don't let us be our own parent.
  if (!empty($client->clid)) {
    $query->entityCondition('entity_id', $client->clid, '!=');
  }
  $result = $query->execute();
  $clients = array('0' => t('<no parent>'));
  if (!empty($result['support_client'])) {
    $entities = entity_load('support_client', array_keys($result['support_client']));
    foreach ($entities as $key => $entity) {
      $clients[$entity->clid] = $entity->name;
    }
  }
  $form['parent'] = array(
    '#title' => t('Parent'),
    '#type' => 'select',
    '#options' => $clients,
    '#default_value' => $client->parent,
    '#description' => t('Optionally nest a client under another client.'),
  );
  $form['mail'] = array(
    '#title' => t('Inbound email integration'),
    '#type' => 'fieldset',
    '#collapsible' => TRUE,
    '#collapsed' => !$client->integrate_email,
  );
  $form['mail']['integrate_email'] = array(
    '#title' => t('Integrate inbound email'),
    '#type' => 'checkbox',
    '#default_value' => $client->integrate_email,
    '#description' => t('Check this box if you would like to fully integrate this client with an email server.  This will allow users to create and update tickets by email, but it requires a more complex configuration as you need to create a unique mailbox for each integrated client.  If you do not check this box, email notifications will still be mailed out as tickets are created and updated, but replies to these tickets will be ignored.') . ' <em>' . t('Inbound email integration requires that your version of PHP was !compiled.', array('!compiled' => l(t('compiled with the IMAP extension'), 'http://php.net/imap'))) . '</em>',
  );
  $form['mail']['server_name'] = array(
    '#title' => t('Server name'),
    '#type' => 'textfield',
    '#description' => t('Hostname or IP address.'),
    '#default_value' => $client->server_name,
    '#description' => t("The hostname or IP address of the mail server where your client's dedicated email address is hosted.  For example, client %foo may receive email at %email, and so the server name may be %hostname.  The server name may also be an IP address, such as %ip.", array('%foo' => 'foo', '%email' => 'foo@support.sample.com', '%hostname' => 'support.sample.com', '%ip' => '192.168.1.1')),
  );
  $form['mail']['server_username'] = array(
    '#title' => t('Server username'),
    '#type' => 'textfield',
    '#default_value' => $client->server_username,
    '#description' => t("The client's email username.  If a client receives email at %email, the server username is often simply %user.  The support module uses this username to log into the mail server to download messages.", array('%email' => 'foo@support.sample.com', '%user' => 'foo')),
  );
  $form['mail']['server_password'] = array(
    '#title' => t('Server password'),
    '#type' => 'textfield',
    '#default_value' => $client->server_password,
    '#description' => t("The client's email password.  The support module uses this password to log into the mail server to download messages. %notice", array('%notice' => t('Note: this is not a password you request from your client.  This is a password you configure on your mail server for managing support tickets for a client.'))),
  );
  $form['mail']['mailbox'] = array(
    '#title' => t('Mailbox'),
    '#type' => 'textfield',
    '#default_value' => $client->mailbox,
    '#description' => t("The mailbox path on your mail server.  You generally do not need to modify this setting."),
  );
  $form['mail']['mailfrom'] = array(
    '#title' => t('From address'),
    '#type' => 'textfield',
    '#default_value' => $client->mailfrom,
    '#description' => t('The email address support should use when sending notification emails.  This must be the same client-specific email address users will send messages to when creating and updating tickets via email.  This will often be comprised of your server username and your server name, such as %email.', array('%email' => 'foo@support.sample.com')),
  );
  $form['mail']['port'] = array(
    '#title' => t('Port'),
    '#type' => 'textfield',
    '#default_value' => $client->port,
    '#description' => t('Specify the TCP port to connect to when downloading email messages.  If using pop3, this port is normally 110.  If using secure pop3, this port is normally 995.  If using imap, this port is normally 143.  If using secure imap this port is normally 993.'),
  );
  $form['mail']['protocol'] = array(
    '#type' => 'radios',
    '#title' => t('Protocol'),
    '#default_value' => 0,
    '#options' => array('pop3', 'secure pop3', 'imap', 'secure imap', 'local file'),
    '#description' => t('Select the protocol used to obtain messages from your mail server.'),
    '#default_value' => $client->protocol,
  );
  $form['mail']['extra'] = array(
    '#title' => t('Optional extra flags'),
    '#type' => 'textfield',
    '#default_value' => $client->extra,
    '#description' => t('The %pop3, %imap and %ssl flags will be automatically set for you depending on your configuration above.  Additional flags can be manually specified here.  If you are having trouble downloading messages, you may need to change these extra flags as !defined.  These flags are ignored when using the %local protocol.', array('!defined' => l(t('defined here'), 'http://php.net/imap_open#function.imap-open.parameters'), '%local' => t('local file'), '%pop3' => t('/pop3'), '%imap' => t('/imap'), '%ssl' => t('/ssl'))),
  );

  $form['mail']['thread_subject'] = array(
    '#type' => 'select',
    '#title' => t('Thread emails using subject'),
    '#options' => array(0 => 'Use global setting', 1 => 'Disabled', 2 => 'Match against new tickets', 3 => 'Match against open tickets', 4 => 'Match against all tickets'),
    '#description' => t('When enabled, the entire message subject will be used to match replies with the original tickets.  By default the !settings configuration will be used, however, you can override this global configuration here.', array('!settings' => l(t('general support settings'), 'admin/support/settings'))),
    '#default_value' => $client->thread_subject,
  );
  $form['mail']['domains'] = array(
    '#type' => 'textfield',
    '#title' => t('Valid email domains'),
    '#default_value' => $client->domains,
    '#description' => t('Enter one or more comma-seperated domains which are allowed to automatically generate new tickets.  If you enter "*" tickets will be allowed from all domains.'),
  );
  $form['mail']['user_creation'] = array(
    '#type' => 'select',
    '#title' => t('Automatic creation of user'),
    '#options' => array('Use global setting', 'Enable', 'Disable'),
    '#description' => t('You can override the global setting for autocreating users by selecting an option in this list.  If enabled, new user accounts will be automatically created if emails are received from unnknown email addresses.  If disabled, notification emails will be sent when emails are discarded notifying the sender that they have to register to be able to create or update support tickets via email.'),
    '#default_value' => $client->user_creation,
  );
  $id = isset($client->clid) ? $client->clid : 0;
  $path = 'support/autocomplete/autosubscribe/' . $id;
  $form['autosubscribe'] = array(
    '#type' => 'textfield',
    '#title' => t('Auto-subscribe'),
    '#autocomplete_path' => $path,
    '#maxlength' => 2048,
    '#default_value' => $client->autosubscribe,
    '#description' => t('Enter one or more comma-seperated usernames to auto-subscribe to !client issues.  These users will receive email notifications for all new tickets and ticket updates for this client.', array('!client' => isset($client->name) ? $client->name : 'client')),
  );
  $form['autoassign'] = array(
    '#type' => 'textfield',
    '#title' => t('Auto-assign'),
    '#autocomplete_path' => 'support/autocomplete/assigned/' . $id,
    '#default_value' => $client->autoassign,
    '#description' => t('Optionally enter a username to auto-assign all new tickets for this client to. If left blank, new tickets will be assigned based on your !settings. Enter %nobody to cause new tickets for this client to be assigned to nobody. Enter %creator to cause new tickets for this client to be assigned to the person creating the ticket.', array('!settings' => l(t('general support settings'), 'admin/support/settings'), '%nobody' => '<nobody>', '%creator' => '<creator>')),
  );
  $form['notes'] = array(
    '#title' => t('Notes'),
    '#type' => 'textarea',
    '#default_value' => $client->notes,
    '#description' => t('Notes are only displayed on this page when editing clients.  They are optional and are provided for administrative purposes.'),
  );
  if (isset($client->clid)) {
    $form['clid'] = array(
      '#type' => 'hidden',
      '#value' => $client->clid,
    );
  }
  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
    '#weight' => 40,
  );
  field_attach_form('support_client', $client, $form, $form_state);
  return $form;
}

function support_client_form_validate($form, &$form_state) {
  $client = $form_state['support_client'];

  if (preg_match('/[&*(),<>?\[\]]/', $form_state['values']['name'])) {
    form_set_error('name', t('You have entered an unsupported character.  The following characters can not be used in the client name: &*(),<>?[]'));
  }
  elseif (!isset($form_state['values']['clid'])) {
    $result = db_query('SELECT 1 FROM {support_client} WHERE name = :value', array(':value' => $form_state['values']['name']));
    foreach ($result as $exists) {
      form_set_error('name', t('The client name %name is already in use. It must be unique.', array('%name' => $form_state['values']['name'])));
    }
  }
  if (!empty($form_state['values']['autosubscribe'])) {
    $subscribe = array();
    $autosubscribe = explode(',', $form_state['values']['autosubscribe']);
    foreach ($autosubscribe as $name) {
      $accounts = user_load_multiple(array(), array('name' => trim($name)));
      $account = array_shift($accounts);
      if (!is_object($account) || !$account->uid) {
        form_set_error('autosubscribe', t('User %username does not exist.', array('%username' => $name)));
      }
      else if (!user_access('access ' . $form_state['values']['name'] . ' tickets', $account) && !user_access('administer support')) {
        form_set_error('autosubscribe', t('User %username does not have permission to access %client tickets.', array('%username' => $name, '%client' => $form_state['values']['name'])));
      }
    }
  }
  if (!empty($form_state['values']['autoassign'])) {
    $autoassign = $form_state['values']['autoassign'];
    switch ($autoassign) {
      case '<nobody>':
      case '<creator>':
        break;
      default:
        $accounts = user_load_multiple(array(), array('name' => trim($form_state['values']['autoassign'])));
        $account = array_shift($accounts);
        $name = $form_state['values']['name'];
        if (empty($account)) {
          form_set_error('autoassign', t('Invalid %field user, %username does not exist.', array('%field' => t('auto-assign'), '%username' => trim($form_state['values']['autoassign']))));
        }
        else if (!user_access('administer support', $account) &&
                 !user_access("access $name tickets", $account)) {
          form_set_error('autoassign', t('Invalid %field  user, %username does not have permission to access %client tickets.', array('%field' => t('auto-assign'), '%username' => trim($form_state['values']['autoassign']), '%client' => $name)));
        }
        break;
    }
  }
  if (!empty($form_state['values']['domains'])) {
    _support_validate_domains('domains', explode(',', $form_state['values']['domains']));
  }
  if ($form_state['values']['integrate_email'] && !function_exists('imap_open')) {
    form_set_error('integrate_email', t('PHP must be !compiled in order to enable inbound email integration.  The IMAP functions are required to support the IMAP, POP3, and local mailbox access methods.', array('!compiled' => l(t('compiled with the IMAP extension'), 'http://php.net/imap'))));
  }
  field_attach_form_validate('support_client', $client, $form, $form_state);
}

/**
 * Render API callback: Checks if a client path name is already taken.
 *
 * @param $value
 *   The path.
 *
 * @return
 *   Whether or not the path is already taken.
 */
function _support_client_path_exists($value) {
  $exists = FALSE;
  $result = db_query('SELECT 1 FROM {support_client} WHERE path = :value', array(':value' => $value));
  foreach ($result as $path) {
    $exists = TRUE;
  }
  return $exists;
}

/**
 * Add/update client information.
 */
function support_client_form_submit($form, &$form_state) {
  if ($form_state['triggering_element']['#value'] == t('Delete')) {
    // Rebuild the form to confirm support client deletion.
    $form_state['rebuild'] = TRUE;
    $form_state['confirm_delete'] = TRUE;
    return;
  }

  $client = entity_ui_form_submit_build_entity($form, $form_state);
  // For the form, we were using 'client_path' as the element for 'path' to prevent
  // conflict with pathauto. We change it back here so it gets saved properly.
  $client->path = $client->client_path;
  unset($client->client_path);

  switch ($client->save()) {
    case SAVED_NEW:
      drupal_set_message(t('Client %client created.', array('%client' => $client->name)));
      break;
    case SAVED_UPDATED:
      drupal_set_message(t('Client %client updated.', array('%client' => $client->name)));
      break;
    case SAVED_DELETED:
      drupal_set_message(t('Client %client deleted.', array('%client' => $client->name)));
      break;
  }

  // clean page cache and block cache
  cache_clear_all();
  // rebuild the menu
  menu_rebuild();

  $form_state['redirect'] = 'admin/support/clients';
}

/**
 * Admin page block.
 */
function support_admin_menu_block_page() {
  $item = menu_get_item();
  if ($content = system_admin_menu_block($item)) {
    return theme('admin_block_content', array('content' => $content));
  }
  else {
    return t('You can not access any administrative items.');
  }
}


function support_admin_settings() {
  $form = array();

  $form['general'] = array(
    '#type' => 'fieldset',
    '#title' => t('General'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );
  $form['general']['support_autocomplete_limit'] = array(
    '#type' => 'select',
    '#title' => t('Auto-complete limit'),
    '#description' => t('A threshold above which select fields and checkboxes will turn into autocomplete text fields.  For example, if set to 20 and a ticket can be assigned to 25 users, the assigned widget will be a textfield requiring you to type the user name.  If set to 20 and a ticket can only be assigned to 10 users, the assigned widget will be a drop down select menu listing all matching users.'),
    '#options' => array(0 => t('always')) + drupal_map_assoc(array(5, 10, 15, 20, 25, 50, 75, 100, 250, 500, 750, 1000)),
    '#default_value' => variable_get('support_autocomplete_limit', 15),
  );
  $form['general']['support_autoassign_ticket'] = array(
    '#type' => 'textfield',
    '#autocomplete_path' => 'support/autocomplete/assigned',
    '#title' => t('Auto-assign new tickets to'),
    '#description' => t('Enter a username to autoassign new tickets to a specific user (the user must have %admin permissions). Enter %creator to autoassign new tickets to the user creating the ticket. Leave blank or enter %nobody to leave new tickets unassigned. This value can be overridden on a per-client basis.', array('%admin' => t('administer support'), '%creator' => '<' . t('creator') . '>', '%nobody' => '<' . t('nobody') . '>')),
    '#default_value' => variable_get('support_autoassign_ticket', '<nobody>'),
  );
  $form['general']['support_default_sort_tickets'] = array(
    '#type' => 'radios',
    '#title' => t('Default sort column'),
    '#description' => t('Ticket views are sorted by clicking on the title of any given column.  This options defines the default sort option if no column title has been clicked.'),
    '#options' => array(SUPPORT_SORT_NONE => t('None'), SUPPORT_SORT_UPDATE => t('Last update'), SUPPORT_SORT_NID => t('Node ID'), SUPPORT_SORT_STATE => t('State'), SUPPORT_SORT_PRIORITY => t('Priority')),
    '#default_value' => variable_get('support_default_sort_tickets', SUPPORT_SORT_UPDATE),
  );
  $form['general']['support_default_sort_order'] = array(
    '#type' => 'radios',
    '#title' => t('Default sort order'),
    '#description' => t('The default sort order.'),
    '#options' => array(t('Ascending'), t('Descending')),
    '#default_value' => variable_get('support_default_sort_order', SUPPORT_SORT_DESC),
  );
  $form['general']['support_secondary_sort_tickets'] = array(
    '#type' => 'radios',
    '#title' => t('Secondary sort column'),
    '#description' => t('Ticket views are sorted by clicking on the title of any given column.  This option allows you to set a secondary sort column.  For example, you can sort tickets by priority, and configure the secondary sort field here to sort all tickets of the same priority by node id. For best performance, do not set a secondary sort column.'),
    '#options' => array(SUPPORT_SORT_NONE => t('None'), SUPPORT_SORT_UPDATE => t('Last update'), SUPPORT_SORT_NID => t('Node ID'), SUPPORT_SORT_STATE => t('State'), SUPPORT_SORT_PRIORITY => t('Priority')),
    '#default_value' => variable_get('support_secondary_sort_tickets', SUPPORT_SORT_NONE),
  );
  $form['general']['support_secondary_sort_order'] = array(
    '#type' => 'radios',
    '#title' => t('Secondary sort order'),
    '#description' => t('If specifying a secondary sort column, you can also specify a secondary sort order..'),
    '#options' => array(t('Ascending'), t('Descending')),
    '#default_value' => variable_get('support_secondary_sort_order', SUPPORT_SORT_DESC),
  );

  $form['comments'] = array(
    '#type' => 'fieldset',
    '#title' => t('Comments'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );
  $form['comments']['support_require_comment'] = array(
    '#type' => 'checkbox',
    '#title' => t('Require comment when bulk-updating tickets'),
    '#description' => t('Require a comment when users with %perm1 or %perm2 permissions bulk-update ticket properties on the ticket overview page.', array('%perm1' => t('edit multiple tickets'), '%perm2' => t('administer support'))),
    '#default_value' => variable_get('support_require_comment', TRUE),
  );
  $form['comments']['support_disable_post_comment'] = array(
    '#type' => 'checkbox',
    '#title' => t('Disable %post header', array('%post' => t('Add new comment'))),
    '#description' => t('Disable the %post header displayed when viewing support tickets.', array('%post' => t('Add new comment'))),
    '#default_value' => variable_get('support_disable_post_comment', FALSE),
  );
  $form['comments']['support_disable_comment_reply'] = array(
    '#type' => 'checkbox',
    '#title' => t('Disable comment reply link'),
    '#description' => t('Disable the reply link displayed under comments for support tickets. This setting is generally only useful if you plan to display comments as a flat-list with the content submission form displayed inline, !link.', array('!link' => l(t('configured here'), 'admin/structure/types/manage/support-ticket'))),
    '#default_value' => variable_get('support_disable_comment_reply', FALSE),
  );

  $form['notification'] = array(
    '#type' => 'fieldset',
    '#title' => t('Notifications'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );
  $form['notification']['support_notifications'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable'),
    '#description' => t('Globally enable support ticket notifications.  Disable this if you don\'t ever want the support module to send email notifications.  Alternatively, you can globally enable notifications and then disable them on a per-client basis.'),
    '#default_value' => variable_get('support_notifications', TRUE),
  );
  $form['notification']['support_admin_notify'] = array(
    '#type' => 'select',
    '#title' => t('Visual feedback'),
    '#description' => t('Provide visual feedback about to whom notifications are being sent.  Feedback can be provided just to users with %perm permissions, or to all users. %warn', array('%perm' => t('administer support'), '%warn' => t('Visual feedback will reveal the email address of all users that receive notifications by displaying a message at the top of the page after a ticket is created or updated.'))),
    '#options' => array(
      FALSE => t('Disabled'),
      1 => t('To users with "!perm" permissions', array('!perm' => t('administer support'))),
      2 => t('To all users'),
    ),
    '#default_value' => variable_get('support_admin_notify', FALSE),
  );
  $form['notification']['support_autosubscribe_creator'] = array(
    '#type' => 'checkbox',
    '#title' => t('Prevent user creating or updating ticket from unsubscribing'),
    '#description' => t('Check this box to prevent the user creating or updating a ticket from unsubscribing from notifications.'),
    '#default_value' => variable_get('support_autosubscribe_creator', FALSE),
  );
  $form['notification']['support_autosubscribe_force'] = array(
    '#type' => 'checkbox',
    '#title' => t('Prevent autosubscribed users from unsubscribing'),
    '#description' => t('Check this box to prevent administratively autosubscribed users from unsubscribing from notifications.'),
    '#default_value' => variable_get('support_autosubscribe_force', FALSE),
  );
  $form['notification']['support_autosubscribe_assigned'] = array(
    '#type' => 'checkbox',
    '#title' => t('Prevent assigned users from unsubscribing'),
    '#description' => t('Check this box to prevent assigned users from unsubscribing from notifications.'),
    '#default_value' => variable_get('support_autosubscribe_assigned', FALSE),
  );

  $form['user'] = array(
    '#type' => 'fieldset',
    '#title' => t('Users'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );
  $form['user']['support_display_user_links'] = array(
    '#type' => 'checkbox',
    '#title' => t('Display support ticket links in profile'),
    '#description' => t('Check this box to display links from a user\'s profile page to create new and view their existing support tickets.'),
    '#default_value' => variable_get('support_display_user_links', TRUE),
  );
  $form['user']['support_autocreate_users'] = array(
    '#title' => t('Auto-create new users on incoming mail'),
    '#type' => 'checkbox',
    '#default_value' => variable_get('support_autocreate_users', TRUE),
    '#description' => t('Check this box if you would like the support module to automatically create new user accounts when emails are received from unknown email addresses.  If you uncheck this box and have "Integrate inbound email" enabled for one or more of your clients, notification emails will be sent when emails are discarded notifying the sender that they have to register to be able to create or update support tickets via mail. This option can also be set on a per-client basis.'),
  );
  $admin = user_load(1);
  $form['user']['support_filter_uid1'] = array(
    '#title' => t('Filter UID1 from ticket assignment and subscription lists'),
    '#type' => 'checkbox',
    '#default_value' => variable_get('support_filter_uid1', FALSE),
    '#description' => t('Check this box to prevent the user with UID 1 (%admin) from showing up in ticket assignment and subscription lists. Tickets can still be assigned to UID 1 if you use autocomplete text fields and manually type the username when assigning a ticket.', array('%admin' => $admin->name)),
  );

  $form['search'] = array(
    '#type' => 'fieldset',
    '#title' => t('Search'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
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

  $form['mail'] = array(
    '#type' => 'fieldset',
    '#title' => t('Mail'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );
  $form['mail']['support_global_mailfrom'] = array(
    '#type' => 'textfield',
    '#title' => t('Default mail from address'),
    '#description' => t('Optionally specify a default <em>from</em> address where support notifications will originate from for clients whose inbound email is not integrated.  If a client\'s inbound email is integrated, you will need to specify the <em>from</em> address on the client\'s configuration page as each client gets a unique <em>from</em> address to allow support to process replies to tickets via email.'),
    '#default_value' => _support_admin_default_mail(),
  );
  $form['mail']['support_cron_download_mail'] = array(
    '#type' => 'checkbox',
    '#title' => t('Download mail via cron'),
    '#description' => t('When checked, support will try to download client emails each time cron is run.  You may want to disable this if you are not integrating support with email, or if you only wish to download mail manually or via the !path.  If automating the download of mail through the support fetch mail path, be sure to !assign permissions to the necessary role(s).', array('!path' => l(t('support fetch mail path'), 'support/fetch'), '!assign' => l(t('assign "download mail via support/fetch"'), 'admin/people/permissions', array('fragment' => 'module-support')))),
    '#default_value' => variable_get('support_cron_download_mail', TRUE),
  );
  $form['mail']['support_global_domains'] = array(
    '#type' => 'textfield',
    '#title' => t('Globally allowed domains'),
    '#description' => t('Optionally enter one or more comma separated domains which are allowed to automatically create tickets via email.  To allow tickets to be created by any domain enter "*". This value can be overridden on a per-client basis.'),
    '#default_value' => check_plain(variable_get('support_global_domains', '*')),
  );
  $form['mail']['support_thread_by_mail_headers'] = array(
    '#type' => 'checkbox',
    '#title' => t('Thread emails using mail headers'),
    '#description' => t('When checked, the %message_id, %in_reply_to and %references mail headers wil be used to match replies with the original tickets.', array('%message_id' => t('message_id'), '%in_reply_to' => t('in_reply_to'), '%references' => t('references'))),
    '#default_value' => variable_get('support_thread_by_mail_headers', TRUE),
  );
  $form['mail']['support_thread_by_subject'] = array(
    '#type' => 'select',
    '#title' => t('Thread emails using subject'),
    '#options' => array(
      1 => 'Disabled',
      2 => 'Match against new tickets',
      3 => 'Match against open tickets',
      4 => 'Match against all tickets',
    ),
    '#description' => t('When enabled, the entire message subject will be used to match replies with the original tickets.  By default the subject will only be matched against other open tickets.'),
    '#default_value' => variable_get('support_thread_by_subject', 3),
  );
  $form['mail']['support_use_aliased_urls'] = array(
    '#type' => 'checkbox',
    '#title' => t('Use aliased URLs in support notification emails'),
    '#description' => t('By default when the support module generates notification emails it uses aliased URLs in any included links.  By unchecking this box, you configure the support module to use unaliased URLs.  You may want to uncheck this box if the aliased paths to tickets changes frequently, as unaliased paths never change.'),
    '#default_value' => variable_get('support_use_aliased_urls', TRUE),
  );

  return system_settings_form($form);
}

/**
 * Be sure valid domains are entered.
 */
function support_admin_settings_validate($form, &$form_state) {
  if (!empty($form_state['values']['support_global_domains'])) {
    _support_validate_domains('support_global_domains', explode(',', trim($form_state['values']['support_global_domains'])));
  }
  if (!empty($form_state['values']['support_global_mailfrom']) && !valid_email_address($form_state['values']['support_global_mailfrom'])) {
    form_set_error('support_global_mailfrom', t('You must enter a valid email address or leave the default mail from address field blank.'));
  }
  if (!empty($form_state['values']['support_autoassign_ticket'])) {
    $autoassign = $form_state['values']['support_autoassign_ticket'];
    switch ($autoassign) {
      case '':
      case '<nobody>':
      case '<creator>':
        break;
      default:
        $accounts = user_load_multiple(array(), array('name' => trim($form_state['values']['support_autoassign_ticket'])));
        $account = array_shift($accounts);
        if (empty($account) || !$account->uid) {
          form_set_error('support_autoassign_ticket', t('Invalid %field user, %username is not a valid user.', array('%field' => t('auto-assign'), '%username' => $form_state['values']['support_autoassign_ticket'])));
        }
        else if (!user_access('administer support', $account)) {
          form_set_error('support_autoassign_ticket', t('Invalid %field user, %username does not have %admin permission.', array('%field' => t('auto-assign'), '%username' => $account->name, '%admin' => t('administer support'))));
        }
    }
  }
}

/**
 * Validate domains by doing a dns lookup on each.
 */
function _support_validate_domains($field, $domains = array()) {
  foreach ($domains as $domain) {
    $domain = trim($domain);
    if ($domain && ($domain != '*' && function_exists('checkdnsrr') && !checkdnsrr($domain))) {
      form_set_error($field, t('Invalid domain: %domain', array('%domain' => $domain)));
    }
  }
}

/**
 * Settings form for email texts
 */
function support_admin_mail_settings() {
  $default_texts = _support_mail_text_default(NULL);
  $textgroups = array(
    'new' => t('New ticket'),
    'comment_new' => t('Updated ticket'),
    'deny' => t('Ticket creation denied'),
  );

  $form = array();
  foreach ($textgroups as $key => $description) {
    $text_key = 'ticket_' . $key;
    $var_key = 'support_mail_' . $text_key;
    $form[$text_key] = array(
      '#type' => 'fieldset',
      '#title' => t('!setting notification', array('!setting' => $description)),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );
    $form[$text_key][$var_key . '_subject'] = array(
      '#type' => 'textfield',
      '#title' => t('Subject'),
      '#description' => t('The email subject for !setting notifications.', array('!setting' => strtolower($description))),
      '#default_value' => variable_get($var_key . '_subject', $default_texts[$text_key . '_subject']),
      '#required' => TRUE,
    );
    $form[$text_key][$var_key . '_body'] = array(
      '#type' => 'textarea',
      '#title' => t('Body'),
      '#description' => t('The email body for !setting notifications.', array('!setting' => strtolower($description))),
      '#default_value' => variable_get($var_key . '_body', $default_texts[$text_key . '_body']),
      '#rows' => 15,
      '#required' => TRUE,
    );
  }
  $form['general'] = array(
    '#type' => 'fieldset',
    '#title' => t('General mail settings'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );
  $form['general']['support_key'] = array(
    '#type' => 'textfield',
    '#title' => t('Notification key prefix'),
    '#description' => t('Used for email threading, appears in notification email subjects as "[!key:12345]"', array('!key' => variable_get('support_key', 'tkt'))),
    '#default_value' => variable_get('support_key', 'tkt'),
    '#required' => TRUE,
  );
  return system_settings_form($form);
}
