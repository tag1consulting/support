<?php

define('SUPPORT_PM_AGGREGATE', 0);
define('SUPPORT_PM_USER', 1);

define('SUPPORT_PM_THISWEEK', 0);
define('SUPPORT_PM_LASTWEEK', 1);
define('SUPPORT_PM_NEXTWEEK', 2);
define('SUPPORT_PM_THISMONTH', 5);
define('SUPPORT_PM_LASTMONTH', 6);
define('SUPPORT_PM_NEXTMONTH', 7);
define('SUPPORT_PM_THISQUARTER', 10);
define('SUPPORT_PM_LASTQUARTER', 11);
define('SUPPORT_PM_NEXTQUARTER', 12);
define('SUPPORT_PM_THISYEAR', 15);
define('SUPPORT_PM_LASTYEAR', 16);
define('SUPPORT_PM_NEXTYEAR', 17);
define('DIVIDER1', 9991);
define('DIVIDER2', 9992);
define('DIVIDER3', 9993);

function support_pm_admin_reports() {
  $form = array();

  // $period must be an integer
  $period = isset($_GET['rp']) ? (int)$_GET['rp'] : NULL;
  // $type must be an integer
  $type = isset($_GET['rt']) ? (int)$_GET['rt'] : NULL;
  // $roles must be a comma-separated list of integer
  $roles = isset($_GET['rr']) ? explode(',', preg_replace('/[^0-9,]/', '', $_GET['rr'])) : NULL;
  // $clients must be a comma-separated list of integer
  $clients = isset($_GET['rc']) ? explode(',', preg_replace('/[^0-9,]/', '', $_GET['rc'])) : NULL;

  if (!is_null($period) && !is_null($period)) {
    switch ($type) {
      case SUPPORT_PM_AGGREGATE:
      default:
        $report_type = 'aggregate';
        break;
      case SUPPORT_PM_USER:
        $report_type = 'user';
        break;
    }

    switch ($period) {
      case SUPPORT_PM_THISWEEK:
      default:
        $start = _support_pm_first_day(time());
        $end = $start + 86400 * 7;
        $days = 7;
        break;
      case SUPPORT_PM_LASTWEEK:
        $start = _support_pm_first_day(time() - 86400 * 7);
        $end = $start + 86400 * 7;
        $days = 7;
        break;
      case SUPPORT_PM_NEXTWEEK:
        $start = _support_pm_first_day(time() + 86400 * 7);
        $end = $start + 86400 * 7;
        $days = 7;
        break;
      case SUPPORT_PM_THISMONTH:
        $start = _support_pm_first_day_month(time());
        $end = _support_pm_last_day_month(time());
        $days = _support_pm_days_in_month(time());
        break;
      case SUPPORT_PM_LASTMONTH:
        $start = _support_pm_first_day_month(time() - 86400 * 31);
        $end = _support_pm_last_day_month(time() - 86400 * 31);
        $days = _support_pm_days_in_month(time() - 86400 * 31);
        break;
      case SUPPORT_PM_NEXTMONTH:
        $start = _support_pm_first_day_month(time() + 86400 * 31);
        $end = _support_pm_last_day_month(time() + 86400 * 31);
        $days = _support_pm_days_in_month(time() + 86400 * 31);
        break;
      case SUPPORT_PM_THISQUARTER:
        // TODO
        break;
      case SUPPORT_PM_LASTQUARTER:
        // TODO
        break;
      case SUPPORT_PM_NEXTQUARTER:
        // TODO
        break;
      case SUPPORT_PM_THISYEAR:
        // TODO
        break;
      case SUPPORT_PM_LASTYEAR:
        // TODO
        break;
      case SUPPORT_PM_NEXTYEAR:
        // TODO
        break;
    }

    $header_details = array('');
    $row = array('<strong>'. t('Plan') .'</strong>');
    $row2 = array('<strong>'. t('Actual') .'</strong>');
    $totals = array('client_plan' => array(), 'user_plan' => array());
    $hours_client = array();
    $hours_user = array();
    for ($i = 0; $i < $days; $i++) {
      $date = $start + 86400 * $i;
      $header_details[] = t('!day<br />!date', array('!day' => format_date($date, 'custom', 'l'), '!date' => format_date($date, 'custom', 'M d')));
      $day = support_pm_day_load($date);
      $client_day = support_pm_aggregate_client($day);
      if (is_array($client_day)) {
        $row[] = theme('support_pm_user_client_hours_details', array('day' => $client_day, 'scale' => 64));
      }
      else {
        $row[] = '';
      }
      // Add up totals
      if (is_array($client_day)) {
        foreach ($client_day as $clid => $data) {
          if (!isset($totals['client_plan'][$clid])) {
            $totals['client_plan'][$clid] = 0;
          }
          $totals['client_plan'][$clid] += $data->hours;
        }
      }
      else if (!is_array($totals['client_plan'])) {
        $totals['client_plan'] = array();
      }

      $user_day = support_pm_aggregate_user($day);
      if (is_array($user_day)) {
        $row_user[] = theme('support_pm_user_client_hours_details', array('day' => $user_day, 'scale' => 64));
      }
      else {
        $row_user[] = '';
      }
      // Add up totals
      if (is_array($user_day)) {
        foreach ($user_day as $uid => $data) {
          if (!isset($totals['user_plan'][$uid])) {
            $totals['user_plan'][$uid] = 0;
          }
          $totals['user_plan'][$uid] += $data->hours;
        }
      }
      else if (!is_array($totals['user_plan'])) {
        $totals['user_plan'] = array();
      }

      // Integrate with the support_timer module, if enabled
      if (module_exists('support_timer')) {
        $hour_client = array();
        $hour_user = array();
        // The support_timer module uses a slightly different date format
        $convert = strtotime(date('d M Y', $date));
        $result = db_query('SELECT tt.time, t.client, n.uid FROM {support_ticket_timer} tt LEFT JOIN {support_ticket} t ON tt.nid = t.nid LEFT JOIN {node} n ON t.nid = n.nid WHERE tt.date = :date', array(':date' => $convert));
        foreach ($result as $timer) {
          if (!isset($hour_client[$timer->client])) {
            $hour_client[$timer->client]->hours = 0;
            $hours_client[$timer->client]->hours = 0;
          }
          if (!isset($hour_user[$timer->uid])) {
            $hour_user[$timer->uid]->hours = 0;
            $hours_user[$timer->uid]->hours = 0;
          }
          $hour_client[$timer->client]->hours += support_pm_timer_to_hours($timer->time);
          $hours_client[$timer->client]->hours += support_pm_timer_to_hours($timer->time);
          $hour_user[$timer->uid]->hours += support_pm_timer_to_hours($timer->time);
          $hours_user[$timer->uid]->hours += support_pm_timer_to_hours($timer->time);
        }
        $result = db_query('SELECT tt.time, t.client, c.uid FROM {support_ticket_comment_timer} tt LEFT JOIN {support_ticket_comment} t ON tt.cid = t.cid LEFT JOIN {comment} c ON t.cid = c.cid WHERE tt.date = :date', array(':date' => $convert));
        foreach ($result as $timer) {
          if (!isset($hour_client[$timer->client])) {
            $hour_client[$timer->client]->hours = 0;
            $hours_client[$timer->client]->hours = 0;
          }
          if (!isset($hour_user[$timer->uid])) {
            $hour_user[$timer->uid]->hours = 0;
            $hours_user[$timer->uid]->hours = 0;
          }
          $hour_client[$timer->client]->hours += support_pm_timer_to_hours($timer->time);
          $hours_client[$timer->client]->hours += support_pm_timer_to_hours($timer->time);
          $hour_user[$timer->uid]->hours += support_pm_timer_to_hours($timer->time);
          $hours_user[$timer->uid]->hours += support_pm_timer_to_hours($timer->time);
        }
        $row2[] = theme('support_pm_user_client_hours_details', array('day' => $hour_client, 'scale' => 64));
        $row_user[] = theme('support_pm_user_client_hours_details', array('day' => $hour_user, 'scale' => 64));
      }
    }
    $rows_details = array($row);
    // Only display actual data if support_timer is enabled to collect it
    if (count($row2) > 1) {
      $rows_details[] = $row2;
      foreach ($hours_client as $clid => $data) {
        // Add up totals
        $totals['client_actual'][$clid] = $data->hours;
      }
    }
    if (count($row_user) > 1) {
      foreach ($hours_user as $uid => $data) {
        // Add up totals
        $totals['user_actual'][$uid] = $data->hours;
      }
    }

    $header_client = array(t('Plan'));
    $client_plan_sum = is_array($totals['client_plan']) ? array_sum($totals['client_plan']) : 0;
    $client_actual_sum = is_array($totals['client_actual']) ? array_sum($totals['client_actual']) : 0;
    $max = $client_plan_sum > $client_actual_sum ? $client_plan_sum : $client_actual_sum;
    $row = array(theme('support_pm_user_hours_summary', array('totals' => $totals['client_plan'], 'load_callback' => 'support_client_load', 'max' => $max, 'message' => t('Not scheduled'))));
    if (count($row2) > 1) {
      $header_client[] = t('Actual');
      $row[] = theme('support_pm_user_hours_summary', array('totals' => $totals['client_actual'], 'load_callback' => 'support_client_load', 'max' => $max, 'message' => t('Not worked')));
    }
    $rows_client = array($row);

    $header_user = array(t('Plan'));
    $user_plan_sum = is_array($totals['user_plan']) ?  array_sum($totals['user_plan']) : 0;
    $user_actual_sum = is_array($totals['user_actual']) ? array_sum($totals['user_actual']) : 0;
    $max = $user_plan_sum > $user_actual_sum ? $user_plan_sum : $user_actual_sum;
    $row = array(theme('support_pm_user_hours_summary', array('totals' => $totals['user_plan'], 'load_callback' => 'user_load', 'max' => $max, 'message' => t('Not scheduled'))));
    if (count($row2) > 1) {
      $header_user[] = t('Actual');
      $row[] = theme('support_pm_user_hours_summary', array('totals' => $totals['user_actual'], 'load_callback' => 'user_load', 'max' => $max, 'message' => t('Not worked')));
    }
    $rows_user = array($row);

    $form['client'] = array(
      '#type' => 'fieldset',
      '#title' => t('Client'),
    );
    $form['client']['client_data'] = array(
      '#type' => 'markup',
      '#markup' => theme('table', array('header' => $header_client, 'rows' => $rows_client, 'attributes' => array('id' => 'support_pm_summary'))),
    );

    $form['user'] = array(
      '#type' => 'fieldset',
      '#title' => t('User'),
    );
    $form['user']['user_data'] = array(
      '#type' => 'markup',
      '#markup' => theme('table', array('header' => $header_user, 'rows' => $rows_user, 'attributes' => array('id' => 'support_pm_summary'))),
    );

    $form['detail'] = array(
      '#type' => 'fieldset',
      '#title' => t('Detail'),
    );
    $form['detail']['detail_data'] = array(
      '#type' => 'markup',
      '#markup' => theme('table', array('header' => $header_details, 'rows' => $rows_details, 'attributes' => array('id' => 'support_pm_week'))),
    );
  }

  // TODO: Custom periods
  $form['report_period'] = array(
    '#type' => 'select',
    '#title' => t('Period'),
    //'#options' => array(SUPPORT_PM_THISWEEK => t('This week'), SUPPORT_PM_LASTWEEK => t('Last week'), SUPPORT_PM_NEXTWEEK => t('Next week'), DIVIDER1 => '--', SUPPORT_PM_THISMONTH => t('This month'), SUPPORT_PM_LASTMONTH => t('Last month'), SUPPORT_PM_NEXTMONTH => t('Next month'), DIVIDER2 => '--', SUPPORT_PM_THISQUARTER => t('This quarter'), SUPPORT_PM_LASTQUARTER => t('Last quarter'), SUPPORT_PM_NEXTQUARTER => t('Next quarter'), DIVIDER3 => '--', SUPPORT_PM_THISYEAR => t('This year'), SUPPORT_PM_LASTYEAR => t('Last year'), SUPPORT_PM_NEXTYEAR => t('Next year')),
    '#options' => array(SUPPORT_PM_THISWEEK => t('This week'), SUPPORT_PM_LASTWEEK => t('Last week'), SUPPORT_PM_NEXTWEEK => t('Next week'), DIVIDER1 => '--', SUPPORT_PM_THISMONTH => t('This month'), SUPPORT_PM_LASTMONTH => t('Last month'), SUPPORT_PM_NEXTMONTH => t('Next month')),
    '#default_value' => !is_null($period) ? $period : 0,
    '#description' => t('Select the duration of your report.'),
    '#required' => TRUE,
  );

  $form['report_type'] = array(
    '#type' => 'select',
    '#title' => t('Type'),
    //'#options' => array(SUPPORT_PM_AGGREGATE => t('Aggregate'), SUPPORT_PM_USER => t('Per-user')),
    '#options' => array(SUPPORT_PM_AGGREGATE => t('Aggregate')),
    '#default_value' => !is_null($type) ? $type : 0,
    '#description' => t("Select the type of report to generate."),
    '#required' => TRUE,
  );

  $form['optional'] = array(
    '#type' => 'fieldset',
    '#title' => t('Optional'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );

  $form['optional']['report_role'] = array(
    '#type' => 'select',
    '#title' => t('Role(s)'),
    '#multiple' => TRUE,
    '#options' => user_roles(),
    '#description' => t('Optionally limit your report to one or more roles.'),
  );

  $available_clients = _support_available_clients();
  if (empty($available_clients)) {
    drupal_set_message(t('You must !create a client before you can generate reports.', array('!create' => l(t('create and enable'), 'admin/support/clients/add'))), 'error');
  }
  $form['optional']['report_client'] = array(
    '#type' => 'select',
    '#title' => t('Client(s)'),
    '#multiple' => TRUE,
    '#options' => $available_clients,
    '#description' => t('Optionally limit your report to one or more clients.'),
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Generate report'),
  );

  return $form;
}

function support_pm_admin_reports_submit($form, &$form_state) {
  $args = array();
  $args['rp'] = $form_state['values']['report_period'];
  $args['rt'] = $form_state['values']['report_type'];
  if (!empty($form_state['values']['report_role'])) {
    $args['rr'] = implode(',', $form_state['values']['report_role']);
  }
  if (!empty($form_state['values']['report_client'])) {
    $args['rc'] = implode(',', $form_state['values']['report_client']);
  }
  drupal_goto('admin/support/plan_report', array('query' => $args));
}

function _support_pm_first_day_month($time) {
  return (strtotime(date('M 1, Y 12:00', $time)));
}

function _support_pm_last_day_month($time) {
  return (strtotime(date('M '. _support_pm_days_in_month($time) .', Y 12:00', $time)));
}

function _support_pm_days_in_month($time) {
  return date('j', (mktime(date('H', $time), date('i', $time), date('s', $time), date('n', $time), -1)));
}

function support_pm_aggregate_client($day) {
  // TODO: Count the # of people involved, give 12 hours per person to breakdown scale
  $aggregate = array();
  foreach ($day as $uid => $client) {
    foreach ($client as $clid => $data) {
      if (isset($aggregate[$clid])) {
        $aggregate[$clid]->hours += $data->hours;
      }
      else {
        $aggregate[$clid] = clone($data);
      }
    }
  }
  return $aggregate;
}

function support_pm_aggregate_user($day) {
  // TODO: Count the # of people involved, give 12 hours per person to breakdown scale
  $aggregate = array();
  foreach ($day as $uid => $client) {
    foreach ($client as $clid => $data) {
      if (isset($aggregate[$uid])) {
        $aggregate[$uid]->hours += $data->hours;
      }
      else {
        $aggregate[$uid] = clone($data);
      }
    }
  }
  return $aggregate;
}

function support_pm_admin_settings() {
  $form = array();

  $color_values = variable_get('support_pm_color_values', array());
  if (empty($color_values)) {
    drupal_set_message(t('You must !url before you can edit colors here.', array('!url' => l(t('run a plan report'), 'admin/support/plan_report'))));
  }
  foreach ($color_values as $type => $values) {
    arsort($values);
    $form[$type] = array(
      '#type' => 'fieldset',
      '#collapsible' => 'true',
      '#title' => check_plain($type),
    );
    $clients = $users = array();
    foreach ($values as $id => $color) {
      $object = NULL;
      switch ($type) {
        case 'client':
          if (!empty($id)) {
            $object = support_client_load($id);
          }
          if (is_object($object)) {
            $title = check_plain($object->name);
            $clients[$id] = $id;
          }
          else {
            // Invalid entry, continue foreach
            continue 2;
          }
          break;
        case 'user':
          if (!empty($id)) {
            $object = user_load($id);
          }
          if (is_object($object)) {
            $title = check_plain($object->name);
            $users[$id] = $id;
          }
          else {
            // Invalid entry, continue foreach
            continue 2;
          }
          break;
        default:
          // Invalid type, continue foreach
          continue 2;
      }
      $form[$type]["$type-$id"] = array(
        '#type' => 'textfield',
        '#title' => $title,
        '#required' => TRUE,
        '#default_value' => $color,
        '#description' => "$title color: <img src=\"". url('support_pm/image/') . $color .'" alt="swatch" height="15" width="15" />',
      );
    }
  }
  if (!empty($clients)) {
    $form['clients'] = array(
      '#type' => 'hidden',
      '#value' => implode(',', $clients),
    );
  }
  if (!empty($users)) {
    $form['users'] = array(
      '#type' => 'hidden',
      '#value' => implode(',', $users),
    );
  }
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Update colors'),
  );
  return $form;
}

// TODO: Validate colors

function support_pm_admin_settings_submit($form, &$form_state) {
  $values = array();
  if (!empty($form_state['values']['clients'])) {
    $clients = explode(',', $form_state['values']['clients']);
    foreach ($clients as $clid) {
      $values['client'][$clid] = $form_state['values']["client-$clid"];
    }
  }
  if (!empty($form_state['values']['users'])) {
    $users = explode(',', $form_state['values']['users']);
    foreach ($users as $uid) {
      $values['user'][$uid] = $form_state['values']["user-$uid"];
    }
  }
  variable_set('support_pm_color_values', $values);
  drupal_set_message('Support plan colors updated.');
}

/**
 * Overview of client projects.
 */
function support_pm_admin_project_overview() {
  $rows = array();

  $header = array(
    array('data' => t('Project'), 'field' => 'sp.project'),
    array('data' => t('Client(s)')),
    array('data' => t('Weight'), 'field'=> 'sp.weight'),
    array('data' => t('Disabled'), 'field'=> 'sp.disabled'),
    array('data' => t('Options')),
  );
  $query = db_select('support_project', 'sp')->extend('TableSort')->extend('PagerDefault');
  $query->fields('sp', array('projid', 'project', 'weight', 'disabled'));
  $query->orderByHeader($header);
  $query->limit(50);
  $result = $query->execute();
  foreach ($result as $project) {
    $options = l(t('edit'), "admin/support/project/$project->projid/edit");

    $clients = array();
    $result2 = db_query('SELECT spc.clid, sc.name FROM {support_project_client} spc LEFT JOIN {support_client} sc ON spc.clid = sc.clid WHERE spc.projid = :project AND spc.clid <> 0', array(':project' => $project->projid));
    foreach ($result2 as $client) {
      $clients[] = check_plain($client->name);
    }
    if (empty($clients)) {
      $clients[] = '<em>'. t('All clients') .'</em>';
    }

    $rows[] = array(
      truncate_utf8(check_plain($project->project), 52, TRUE, TRUE),
      implode(', ', $clients),
      number_format($project->weight),
      $project->disabled ? t('Disabled') : t('Active'),
      $options,
    );
  }

  if (empty($rows)) {
    drupal_set_message(t('There are currently no projects defined.'));
  }
  $output = theme('table', array('header' => $header, 'rows' => $rows));
  $output .= theme('pager');
  return $output;
}

function support_pm_admin_project_form($form, &$form_state, $project = array()) {
  $form['project'] = array(
    '#title' => t('Project'),
    '#type' => 'textfield',
    '#required' => TRUE,
    '#maxlength' => 255,
    '#default_value' => isset($project->project) ? $project->project : '',
    '#description' => t('The project name.  This name may appear on invoices.'),
  );

  $form['path'] = array(
    '#title' => t('Path'),
    '#type' => 'textfield',
    '#required' => TRUE,
    '#maxlength' => 255,
    '#default_value' => isset($project->path) ? $project->path : '',
    '#description' => t('A url friendly project path using alpha-numerics, "-", and "_".'),
  );

  $form['disabled'] = array(
    '#title' => t('Disabled'),
    '#type' => 'checkbox',
    '#default_value' => isset($project->disabled) ? $project->disabled : 0,
    '#description' => t('Disabled projects won\'t show up as an option when creating new tickets.'),
  );

  $form['weight'] = array(
    '#title' => t('Weight'),
    '#type' => 'weight',
    '#default_value' => isset($project->weight) ? $project->weight : 0,
    '#description' => t('When multiple projects are available, the project with the smallest (negative) weight will be selected as the default.'),
  );

  $clients = array(
    0 => t('-- All clients --'),
  ) + _support_clients_load();
  $form['clids'] = array(
    '#title' => t('Clients'),
    '#type' => 'select',
    '#options' => $clients,
    '#multiple' => TRUE,
    '#required' => TRUE,
    '#size' => count($clients) > 5 ? count($clients) : 5,
    '#default_value' => isset($project->clids) ? $project->clids : array(0),
    '#description' => t('Select the client(s) this project applies to.'),
  );

  if (!empty($project)) {
    $form['#project'] = $project;
  }

  $form['actions']['submit'] = array(
    '#value' => t('Save'),
    '#type' => 'submit',
  );
  if (isset($project->projid)) {
    $form['actions']['delete'] = array(
      '#value' => t('Delete'),
      '#type' => 'submit',
      '#submit' => array('support_pm_admin_project_form_delete_submit'),
    );
    $form['actions']['cancel'] = array(
      '#markup' => l(t('Cancel'), 'admin/support/project'),
    );
  }

  return $form;
}

/**
 * Save new or updated project definition.
 */
function support_pm_admin_project_form_validate($form, &$form_state) {
  $path = $form_state['values']['path'];
  $projid = isset($form['#project']) ? $form['#project']->projid : 0;
  if ($path != preg_replace('/[^0-9a-zA-Z_-]/', '', $path)) {
    form_set_error('path', t('Path %path contains invalid characters.', array('%path' => $path)));
  }
  if (strtolower($path) == 'null') {
    form_set_error('path', t('The path %path is reserved by the system, please choose a different path.', array('%path' => strtolower($path))));
  }
  if ($path && $projid) {
    $projid = db_query('SELECT projid FROM {support_project} WHERE path = :path AND projid != :project', array(':path' => $path, ':project' => $projid))->fetchField();
  }
  else if ($path) {
    $projid = db_query('SELECT projid FROM {support_project} WHERE path = :path', array(':path' => $path))->fetchField();
  }
  else {
    $projid = NULL;
  }
  if ($projid) {
    form_set_error('path', t('Path %path is already being used by another project.', array('%path' => $path)));
  }
}

/**
 * Save new or updated project definition.
 */
function support_pm_admin_project_form_submit($form, &$form_state) {
  $projid = 0;
  if (!empty($form['#project']->projid)) {
    $projid = $form['#project']->projid;
  }

  $fields = array(
    'project' => $form_state['values']['project'],
    'path' => $form_state['values']['path'],
    'disabled' => $form_state['values']['disabled'],
    'weight' => $form_state['values']['weight'],
  );

  if ($projid) {
    db_update('support_project')->fields($fields)->condition('projid', $projid)->execute();
    drupal_set_message(t('Updated %project project.', array('%project' => $form_state['values']['project'])));
  }
  else {
    $projid = db_insert('support_project')->fields($fields)->execute();
    drupal_set_message(t('Created %project project.', array('%project' => $form_state['values']['project'])));
  }

  db_delete('support_project_client')->condition('projid', $projid)->execute();
  foreach ($form_state['values']['clids'] as $clid) {
    db_insert('support_project_client')->fields(array(
      'projid' => $projid,
      'clid' => $clid,
    ))->execute();
  }

  menu_rebuild();
  drupal_goto('admin/support/project');
}

/**
 * Button submit function: handle the 'Delete' button on the project form.
 */
function support_pm_admin_project_form_delete_submit($form, &$form_state) {
  $destination = array();
  if (isset($_GET['destination'])) {
    $destination = drupal_get_destination();
    unset($_GET['destination']);
  }
  $project = $form['#project'];
  $form_state['redirect'] = array('admin/support/project/' . $project->projid . '/delete', array('query' => $destination));
}

function support_pm_admin_project_delete_form($form, &$form_state, $project) {
  $form['#project'] = $project;
  return confirm_form($form, t('Are you sure you want to delete %project?', array('%project' => $project->project)), 'admin/support/project', t('This action cannot be undone.'), t('Delete'), t('Cancel'));
}

function support_pm_admin_project_delete_form_submit($form, &$form_state) {
  $project = $form['#project'];
  drupal_set_message(t('Deleted %project project.', array('%project' => $project->project)));
  db_delete('support_project')->condition('projid', $project->projid)->execute();
  db_delete('support_project_client')->condition('projid', $project->projid)->execute();
  db_delete('support_project_ticket')->condition('projid', $project->projid)->execute();
  $form_state['redirect'] = 'admin/support/project';
}
