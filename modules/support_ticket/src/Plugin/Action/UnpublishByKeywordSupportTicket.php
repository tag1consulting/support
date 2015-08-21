<?php

/**
 * @file
 * Contains \Drupal\support_ticket\Plugin\Action\UnpublishByKeywordSupportTicket.
 */

namespace Drupal\support_ticket\Plugin\Action;

use Drupal\Component\Utility\Tags;
use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Unpublishes a support ticket containing certain keywords.
 *
 * @Action(
 *   id = "support_ticket_unpublish_by_keyword_action",
 *   label = @Translation("Unpublish support ticket containing keyword(s)"),
 *   type = "support_ticket"
 * )
 */
class UnpublishByKeywordSupportTicket extends ConfigurableActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($support_ticket = NULL) {
    foreach ($this->configuration['keywords'] as $keyword) {
      $elements = support_ticket_view(clone $support_ticket);
      if (strpos(drupal_render($elements), $keyword) !== FALSE || strpos($support_ticket->label(), $keyword) !== FALSE) {
        $support_ticket->setPublished(FALSE);
        $support_ticket->save();
        break;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'keywords' => array(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['keywords'] = array(
      '#title' => t('Keywords'),
      '#type' => 'textarea',
      '#description' => t('The ticket will be unpublished if it contains any of the phrases above. Use a case-sensitive, comma-separated list of phrases. Example: funny, bungee jumping, "Company, Inc."'),
      '#default_value' => Tags::implode($this->configuration['keywords']),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['keywords'] = Tags::explode($form_state->getValue('keywords'));
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\support_ticket\SupportTicketInterface $object */
    $access = $object->access('update', $account, TRUE)
      ->andIf($object->status->access('edit', $account, TRUE));

    return $return_as_object ? $access : $access->isAllowed();
  }

}
