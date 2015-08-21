<?php

/**
 * @file
 * Contains \Drupal\support_ticket\Form\DeleteMultiple.
 */

namespace Drupal\support_ticket\Form;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides a support ticket deletion confirmation form.
 */
class DeleteMultiple extends ConfirmFormBase {

  /**
   * The array of support tickets to delete.
   *
   * @var string[][]
   */
  protected $supportTicketInfo = array();

  /**
   * The tempstore factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The support ticket storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $manager;

  /**
   * Constructs a DeleteMultiple form object.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Entity\EntityManagerInterface $manager
   *   The entity manager.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityManagerInterface $manager) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->storage = $manager->getStorage('support_ticket');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'support_ticket_multiple_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->formatPlural(count($this->supportTicketInfo), 'Are you sure you want to delete this ticket?', 'Are you sure you want to delete these tickets?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('system.admin_support');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->supportTicketInfo = $this->tempStoreFactory->get('support_ticket_multiple_delete_confirm')->get(\Drupal::currentUser()->id());
    if (empty($this->supportTicketInfo)) {
      return new RedirectResponse($this->getCancelUrl()->setAbsolute()->toString());
    }
    /** @var \Drupal\support_ticket\SupportTicketInterface[] $support_tickets */
    $support_tickets = $this->storage->loadMultiple(array_keys($this->supportTicketInfo));

    $items = [];
    foreach ($this->supportTicketInfo as $id => $langcodes) {
      foreach ($langcodes as $langcode) {
        $support_ticket = $support_tickets[$id]->getTranslation($langcode);
        $key = $id . ':' . $langcode;
        $default_key = $id . ':' . $support_ticket->getUntranslated()->language()->getId();

        // If we have a translated entity we build a nested list of translations
        // that will be deleted.
        $languages = $support_ticket->getTranslationLanguages();
        if (count($languages) > 1 && $support_ticket->isDefaultTranslation()) {
          $names = [];
          foreach ($languages as $translation_langcode => $language) {
            $names[] = $language->getName();
            unset($items[$id . ':' . $translation_langcode]);
          }
          $items[$default_key] = [
            'label' => [
              '#markup' => $this->t('@label (Original translation) - <em>The following ticket translations will be deleted:</em>', ['@label' => $support_ticket->label()]),
            ],
            'deleted_translations' => [
              '#theme' => 'item_list',
              '#items' => $names,
            ],
          ];
        }
        elseif (!isset($items[$default_key])) {
          $items[$key] = $support_ticket->label();
        }
      }
    }

    $form['support_tickets'] = array(
      '#theme' => 'item_list',
      '#items' => $items,
    );
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('confirm') && !empty($this->supportTicketInfo)) {
      $total_count = 0;
      $delete_support_tickets = [];
      /** @var \Drupal\Core\Entity\ContentEntityInterface[][] $delete_translations */
      $delete_translations = [];
      /** @var \Drupal\support_ticket\SupportTicketInterface[] $support_tickets */
      $support_tickets = $this->storage->loadMultiple(array_keys($this->supportTicketInfo));

      foreach ($this->supportTicketInfo as $id => $langcodes) {
        foreach ($langcodes as $langcode) {
          $support_ticket = $support_tickets[$id]->getTranslation($langcode);
          if ($support_ticket->isDefaultTranslation()) {
            $delete_support_tickets[$id] = $support_ticket;
            unset($delete_translations[$id]);
            $total_count += count($support_ticket->getTranslationLanguages());
          }
          elseif (!isset($delete_support_tickets[$id])) {
            $delete_translations[$id][] = $support_ticket;
          }
        }
      }

      if ($delete_support_tickets) {
        $this->storage->delete($delete_support_tickets);
        $this->logger('content')->notice('Deleted @count tickets.', array('@count' => count($delete_support_tickets)));
      }

      if ($delete_translations) {
        $count = 0;
        foreach ($delete_translations as $id => $translations) {
          $support_ticket = $support_tickets[$id]->getUntranslated();
          foreach ($translations as $translation) {
            $support_ticket->removeTranslation($translation->language()->getId());
          }
          $support_ticket->save();
          $count += count($translations);
        }
        if ($count) {
          $total_count += $count;
          $this->logger('content')->notice('Deleted @count ticket translations.', array('@count' => $count));
        }
      }

      if ($total_count) {
        drupal_set_message($this->formatPlural($total_count, 'Deleted 1 ticket.', 'Deleted @count tickets.'));
      }

      $this->tempStoreFactory->get('support_ticket_multiple_delete_confirm')->delete(\Drupal::currentUser()->id());
    }

    $form_state->setRedirect('system.admin_support');
  }

}
