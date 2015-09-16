<?php

/**
 * @file
 * Contains \Drupal\support_ticket\Form\SupportTypeDeleteConfirm.
 */

namespace Drupal\support_ticket\Form;

use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for support ticket type deletion.
 */
class SupportTicketTypeDeleteConfirm extends EntityDeleteForm {

  /**
   * The query factory to create entity queries.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * Constructs a new SupportTicketTypeDeleteConfirm object.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The entity query object.
   */
  public function __construct(QueryFactory $query_factory) {
    $this->queryFactory = $query_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $num_support_tickets = $this->queryFactory->get('support_ticket')
      ->condition('support_ticket_type', $this->entity->id())
      ->count()
      ->execute();
    if ($num_support_tickets) {
      $caption = '<p>' . $this->formatPlural($num_support_tickets, '%type is used by 1 ticket on your site. You can not remove this support ticket type until you have removed all of the %type tickets.', '%type is used by @count tickets on your site. You may not remove %type until you have removed all of the %type tickets.', array('%type' => $this->entity->label())) . '</p>';
      $form['#title'] = $this->getQuestion();
      $form['description'] = array('#markup' => $caption);
      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

}
