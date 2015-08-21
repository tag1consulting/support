<?php

/**
 * @file
 * Contains \Drupal\support_ticket\Plugin\views\argument\UidRevision.
 */

namespace Drupal\support_ticket\Plugin\views\argument;

use Drupal\user\Plugin\views\argument\Uid;

/**
 * Filter handler to accept a user id to check for support tickets that
 * user posted or created a revision on.
 *
 * @ViewsArgument("support_ticket_uid_revision")
 */
class UidRevision extends Uid {

  public function query($group_by = FALSE) {
    $this->ensureMyTable();
    $placeholder = $this->placeholder();
    $this->query->addWhereExpression(0, "$this->tableAlias.revision_uid = $placeholder OR ((SELECT COUNT(DISTINCT vid) FROM {support_ticket_revision} nr WHERE nfr.revision_uid = $placeholder AND nr.stid = $this->tableAlias.stid) > 0)", array($placeholder => $this->argument));
  }

}
