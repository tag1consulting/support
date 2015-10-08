<?php

/**
 * @file
 * Contains \Drupal\support_ticket\Controller\SupportTicketController.
 */

namespace Drupal\support_ticket\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\support_ticket\SupportTicketTypeInterface;
use Drupal\support_ticket\SupportTicketInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Support Ticket routes.
 */
class SupportTicketController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a SupportTicketController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(DateFormatterInterface $date_formatter, RendererInterface $renderer) {
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('renderer')
    );
  }


  /**
   * Displays add content links for available support ticket types.
   *
   * Redirects to support_ticket/add/[type] if only one support ticket type is available.
   *
   * @return array
   *   A render array for a list of the support ticket types that can be added; however,
   *   if there is only one support ticket type defined for the site, the function
   *   redirects to the support ticket add page for that one support ticket type and does
   *   not return at all.
   *
   * @see support_ticket_menu()
   */
  public function addPage() {
    $build = [
      '#theme' => 'support_ticket_add_list',
      '#cache' => [
        'tags' => $this->entityManager()->getDefinition('support_ticket_type')->getListCacheTags(),
      ],
    ];

    $types = array();

    // Only use support ticket types the user has access to.
    foreach ($this->entityManager()->getStorage('support_ticket_type')->loadMultiple() as $type) {
      $access = $this->entityManager()->getAccessControlHandler('support_ticket')->createAccess($type->id(), NULL, [], TRUE);
      if ($access->isAllowed()) {
        $types[$type->id()] = $type;
      }
      $this->renderer->addCacheableDependency($build, $access);
    }

    // Bypass the support_ticket/add listing if only one support ticket type is available.
    if (count($types) == 1) {
      $type = array_shift($types);
      return $this->redirect('support_ticket.add', array('support_ticket_type' => $type->id()));
    }
    $build['#content'] = $types;

    return $build;
  }

  /**
   * Provides the support ticket submission form.
   *
   * @param \Drupal\support_ticket\SupportTicketTypeInterface $support_ticket_type
   *   The support ticket type entity for the support ticket.
   *
   * @return array
   *   A support ticket submission form.
   */
  public function add(SupportTicketTypeInterface $support_ticket_type) {
    $support_ticket = $this->entityManager()->getStorage('support_ticket')->create(array(
      'support_ticket_type' => $support_ticket_type->id(),
    ));

    $form = $this->entityFormBuilder()->getForm($support_ticket);

    return $form;
  }

  /**
   * Displays a support_ticket revision.
   *
   * @param int $supprt_ticket_revision
   *   The support ticket revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($support_ticket_revision) {
    $support_ticket = $this->entityManager()->getStorage('support_ticket')->loadRevision($support_ticket_revision);
    $support_ticket_view_controller = new SupportTicketViewController($this->entityManager, $this->renderer);
    $page = $support_ticket_view_controller->view($support_ticket);
    unset($page['support_tickets'][$support_ticket->id()]['#cache']);
    return $page;
  }

  /**
   * Page title callback for a support ticket revision.
   *
   * @param int $support_ticket_revision
   *   The support ticket revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($support_ticket_revision) {
    $support_ticket = $this->entityManager()->getStorage('support_ticket')->loadRevision($support_ticket_revision);
    return $this->t('Revision of %title from %date', array('%title' => $support_ticket->label(), '%date' => format_date($support_ticket->getRevisionCreationTime())));
  }

  /**
   * Generates an overview table of older revisions of a support ticket.
   *
   * @param \Drupal\support_ticket\SupportTicketInterface $support_ticket
   *   A support_ticket object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(SupportTicketInterface $support_ticket) {
    $account = $this->currentUser();
    $support_ticket_storage = $this->entityManager()->getStorage('support_ticket');
    $type = $support_ticket->getType();

    $build = array();
    $build['#title'] = $this->t('Revisions for %title', array('%title' => $support_ticket->label()));
    $header = array($this->t('Revision'), $this->t('Operations'));

    $revert_permission = (($account->hasPermission("revert $type revisions") || $account->hasPermission('revert all revisions') || $account->hasPermission('administer support tickets')) && $support_ticket->access('update'));
    $delete_permission =  (($account->hasPermission("delete $type revisions") || $account->hasPermission('delete all revisions') || $account->hasPermission('administer support tickets')) && $support_ticket->access('delete'));

    $rows = array();

    $vids = $support_ticket_storage->revisionIds($support_ticket);

    foreach (array_reverse($vids) as $vid) {
      $revision = $support_ticket_storage->loadRevision($vid);
      $username = [
        '#theme' => 'username',
        '#account' => $revision->uid->entity,
      ];

      // Use revision link to link to revisions that are not active.
      $date = $this->dateFormatter->format($revision->revision_timestamp->value, 'short');
      if ($vid != $support_ticket->getRevisionId()) {
        $link = $this->l($date, new Url('entity.support_ticket.revision', ['support_ticket' => $support_ticket->id(), 'support_ticket_revision' => $vid]));
      }
      else {
        $link = $support_ticket->link($date);
      }

      $row = [];
      $column = [
        'data' => [
          '#type' => 'inline_template',
          '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
          '#context' => [
            'date' => $link,
            'username' => $this->renderer->renderPlain($username),
            'message' => ['#markup' => $revision->revision_log->value],
          ],
        ],
      ];
      // @todo Simplify once https://www.drupal.org/node/2334319 lands.
      $this->renderer->addCacheableDependency($column['data'], $username);
      $row[] = $column;

      if ($vid == $support_ticket->getRevisionId()) {
        $row[0]['class'] = ['revision-current'];
        $row[] = [
          'data' => [
            '#prefix' => '<em>',
            '#markup' => $this->t('current revision'),
            '#suffix' => '</em>',
          ],
          'class' => ['revision-current'],
        ];
      }
      else {
        $links = [];
        if ($revert_permission) {
          $links['revert'] = [
            'title' => $this->t('Revert'),
            'url' => Url::fromRoute('support_ticket.revision_revert_confirm', ['support_ticket' => $support_ticket->id(), 'support_ticket_revision' => $vid]),
          ];
        }

        if ($delete_permission) {
          $links['delete'] = [
            'title' => $this->t('Delete'),
            'url' => Url::fromRoute('support_ticket.revision_delete_confirm', ['support_ticket' => $support_ticket->id(), 'support_ticket_revision' => $vid]),
          ];
        }

        $row[] = [
          'data' => [
            '#type' => 'operations',
            '#links' => $links,
          ],
        ];
      }

      $rows[] = $row;
    }

    $build['support_ticket_revisions_table'] = array(
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
      '#attached' => array(
        'library' => array('support_ticket/drupal.support_ticket.admin'),
      ),
    );

    return $build;
  }

  /**
   * The _title_callback for the support_ticket.add route.
   *
   * @param \Drupal\support_ticket\SupportTicketTypeInterface $support_ticket_type
   *   The current support ticket.
   *
   * @return string
   *   The page title.
   */
  public function addPageTitle(SupportTicketTypeInterface $support_ticket_type) {
    return $this->t('Create @name', array('@name' => $support_ticket_type->label()));
  }

}
