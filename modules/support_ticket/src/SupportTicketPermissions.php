<?php

/**
 * @file
 * Contains \Drupal\support\SupportTicketPermissions.
 */

namespace Drupal\support;

use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\support\Entity\SupportTicketType;

/**
 * Defines a class containing permission callbacks.
 */
class SupportTicketPermissions {

  use StringTranslationTrait;
  use UrlGeneratorTrait;

  /**
   * Gets an array of support ticket type permissions.
   *
   * @return array
   *   The support ticket type permissions.
   *   @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function supportTicketTypePermissions() {
    $perms = array();
    // Generate support ticket permissions for all support ticket types.
    foreach (SupportTicketType::loadMultiple() as $type) {
      $perms += $this->buildPermissions($type);
    }

    return $perms;
  }

  /**
   * Builds a standard list of support ticket permissions for a given type.
   *
   * @param \Drupal\support\Entity\SupportTicketType $type
   *   The machine name of the support ticket type.
   *
   * @return array
   *   An array of permission names and descriptions.
   */
  protected function buildPermissions(SupportTicketType $type) {
    $type_id = $type->id();
    $type_params = array('%type_name' => $type->label());

    return array(
      "create $type_id content" => array(
        'title' => $this->t('%type_name: Create new content', $type_params),
      ),
      "edit own $type_id content" => array(
        'title' => $this->t('%type_name: Edit own content', $type_params),
      ),
      "edit any $type_id content" => array(
        'title' => $this->t('%type_name: Edit any content', $type_params),
      ),
      "delete own $type_id content" => array(
        'title' => $this->t('%type_name: Delete own content', $type_params),
      ),
      "delete any $type_id content" => array(
        'title' => $this->t('%type_name: Delete any content', $type_params),
      ),
      "view $type_id revisions" => array(
        'title' => $this->t('%type_name: View revisions', $type_params),
      ),
      "revert $type_id revisions" => array(
        'title' => $this->t('%type_name: Revert revisions', $type_params),
        'description' => t('Role requires permission <em>view revisions</em> and <em>edit rights</em> for support tickets in question, or <em>administer support tickets</em>.'),
      ),
      "delete $type_id revisions" => array(
        'title' => $this->t('%type_name: Delete revisions', $type_params),
        'description' => $this->t('Role requires permission to <em>view revisions</em> and <em>delete rights</em> for support tickets in question, or <em>administer support tickets</em>.'),
      ),
    );
  }

}
