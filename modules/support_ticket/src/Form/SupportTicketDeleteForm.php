<?php

/**
 * @file
 * Contains \Drupal\support_ticket\Form\SupportTicketDeleteForm.
 */

namespace Drupal\support_ticket\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;

/**
 * Provides a form for deleting a support ticket.
 */
class SupportTicketDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    /** @var \Drupal\support_ticket\SupportTicketInterface $entity */
    $entity = $this->getEntity();

    $support_ticket_type_storage = $this->entityManager->getStorage('support_ticket_type');
    $support_ticket_type = $support_ticket_type_storage->load($entity->bundle())->label();

    if (!$entity->isDefaultTranslation()) {
      return $this->t('@language translation of the @type %label has been deleted.', [
        '@language' => $entity->language()->getName(),
        '@type' => $support_ticket_type,
        '%label' => $entity->label(),
      ]);
    }

    return $this->t('The @type %title has been deleted.', array(
      '@type' => $support_ticket_type,
      '%title' => $this->getEntity()->label(),
    ));
  }

  /**
   * {@inheritdoc}
   */
  protected function logDeletionMessage() {
    /** @var \Drupal\support_ticket\SupportTicketInterface $entity */
    $entity = $this->getEntity();
    $this->logger('content')->notice('@type: deleted %title.', ['@type' => $entity->getType(), '%title' => $entity->label()]);
  }

}
