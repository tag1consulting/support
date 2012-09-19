<?php
/**
 * @file
 * UI controllers for support entities.
 */

class SupportClientUIController extends EntityDefaultUIController {

  /**
   * Generates the render array for a overview table for arbitrary entities
   * matching the given conditions.
   *
   * @param $conditions
   *   An array of conditions as needed by entity_load().
   *
   * @return Array
   *   A renderable array.
   */
  public function overviewTable($conditions = array()) {
    // Used for _support_admin_default_mail().
    module_load_include('inc', 'support', 'support.admin');
    $rows = array();
    $header = array(
      array(
        'data' => t('Client name'),
        'field' => 'name',
        'type' => 'property',
        'specifier' => 'name',
      ),
      array(
        'data' => t('Status'),
        'field' => 'status',
        'type' => 'property',
        'specifier' => 'status',
      ),
      array(
        'data' => t('Inbound email'),
        'field' => 'integrate_email',
        'type' => 'property',
        'specifier' => 'integrate_email',
      ),
      array(
        'data' => t('From address'),
        'field' => 'mailfrom',
        'type' => 'property',
        'specifier' => 'mailfrom',
      ),
    );

    if (!empty($this->entityInfo['exportable'])) {
      $header[] = t('Status');
    }
    // Add operations with the right colspan.
    $field_ui = !empty($this->entityInfo['bundle of']) && module_exists('field_ui');
    $exportable = !empty($this->entityInfo['exportable']);
    $colspan = 4;
    $colspan = $field_ui ? $colspan + 2 : $colspan;
    $colspan = $exportable ? $colspan + 1 : $colspan;
    $header[] = array('data' => t('Operations'), 'colspan' => $colspan);


    $query = new EntityFieldQuery;
    $result = $query
      ->entityCondition('entity_type', 'support_client')
      ->tableSort($header)
      ->pager(50)
      ->execute();
    if (!empty($result['support_client'])) {
      $clients = entity_load('support_client', array_keys($result['support_client']), $conditions);
      foreach ($clients as $entity) {
          $rows[] = $this->overviewTableRow($conditions, entity_id($this->entityType, $entity), $entity);
      }
    }
    $render = array();
    $render[] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => t('None.'),
    );
    return $render;
  }

  protected function overviewTableRow($conditions, $id, $entity, $additional_cols = array()) {
    $entity_uri = entity_uri($this->entityType, $entity);

    $row[] = array('data' => array(
      '#theme' => 'entity_ui_overview_item',
      '#label' => entity_label($this->entityType, $entity),
      '#name' => !empty($this->entityInfo['exportable']) ? entity_id($this->entityType, $entity) : FALSE,
      '#url' => $entity_uri ? $entity_uri : FALSE,
      '#entity_type' => $this->entityType),
    );

    $row[] = $entity->status == 1 ? t('enabled') : t('disabled');
    $row[] = $entity->integrate_email ? t('integrated') : t('not integrated');
    $row[] = $entity->integrate_email ? check_plain($entity->mailfrom) : '<em>' . t('using default') . '</em>: ' . l(_support_admin_default_mail(), 'admin/support/settings');


    // Add in any passed additional cols.
    foreach ($additional_cols as $col) {
      $row[] = $col;
    }

    // Add a row for the exportable status.
    if (!empty($this->entityInfo['exportable'])) {
      $row[] = array('data' => array(
        '#theme' => 'entity_status',
        '#status' => $entity->{$this->statusKey},
      ));
    }
    // In case this is a bundle, we add links to the field ui tabs.
    $field_ui = !empty($this->entityInfo['bundle of']) && module_exists('field_ui');
    // For exportable entities we add an export link.
    $exportable = !empty($this->entityInfo['exportable']);
    $colspan = 4;
    $colspan = $field_ui ? $colspan + 2 : $colspan;
    $colspan = $exportable ? $colspan + 1 : $colspan;

    // Add operations depending on the status.
    if (entity_has_status($this->entityType, $entity, ENTITY_FIXED)) {
      $row[] = array('data' => l(t('clone'), $this->path . '/manage/' . $id . '/clone'), 'colspan' => $colspan);
    }
    else {
      $row[] = l(t('edit'), $this->path . '/manage/' . $id);

      $row[] = l(t('fetch'), $this->path . '/' . $id . '/fetch');

      if ($field_ui) {
        $row[] = l(t('manage fields'), $this->path . '/manage/' . $id . '/fields');
        $row[] = l(t('manage display'), $this->path . '/manage/' . $id . '/display');
      }

      $row[] = l(t('clone'), $this->path . '/manage/' . $id . '/clone');
      if (empty($this->entityInfo['exportable']) || !entity_has_status($this->entityType, $entity, ENTITY_IN_CODE)) {
        $row[] = l(t('delete'), $this->path . '/manage/' . $id . '/delete', array('query' => drupal_get_destination()));
      }
      elseif (entity_has_status($this->entityType, $entity, ENTITY_OVERRIDDEN)) {
        $row[] = l(t('revert'), $this->path . '/manage/' . $id . '/revert', array('query' => drupal_get_destination()));
      }
      else {
        $row[] = '';
      }
    }
    if ($exportable) {
      $row[] = l(t('export'), $this->path . '/manage/' . $id . '/export');
    }
    return $row;
  }
};
