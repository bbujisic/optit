<?php

namespace Drupal\optit\Controller;

use Drupal;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\optit\Optit\Interest;
use Drupal\optit\Optit\Optit;

/**
 * Provides the interests page.
 */
class InterestController extends ControllerBase {

  /**
   * Returns the list of available interests.
   */
  public function listPage($keyword_id) {

    $optit = Optit::create();
    $interests = $optit->interestsGet($keyword_id);

//    // Decide page
//    $page = (isset($_GET['page']) ? $_GET['page'] + 1 : 1);
//
//    // Run query against the API.
//    $interests = $optit->setPage($page)
//      ->interestsGet($keyword_id);


    $build = [];

    if (count($interests) == 0) {
      $build['empty'] = [
        '#prefix' => '<div class="empty-page">',
        '#markup' => $this->t('Your interest list is empty.'),
        '#suffix' => '</div>',
      ];
      return $build;
    }

    // Start building vars for theme_table.
    $header = array(
      $this->t('ID'),
      $this->t('Name'),
      $this->t('Description'),
      $this->t('Created at'),
      $this->t('Number of subscriptions'),
      $this->t('Status'),
      $this->t('Actions')
    );

    $rows = [];

    // Iterate through received keywords and fill in table rows.
    /** @var Interest $interest */
    foreach ($interests as $interest) {

      // Prepare links for actions column of the list.
      $actions = array();

      $actions[] = array(
        'title' => t('View subscriptions'),
        'url' => Url::fromRoute('optit.structure_keywords')
        //'href' => "admin/structure/optit/keywords/{$keyword_id}/interests/{$interest->get('id')}/subscriptions"
      );
      $actions[] = array(
        'title' => t('Subscribe a member'),
        'url' => Url::fromRoute('optit.structure_keywords')
        //'href' => "admin/structure/optit/keywords/{$keyword_id}/interests/{$interest->get('id')}/subscriptions/new"
      );
      if (Drupal::moduleHandler()->moduleExists('optit_send')) {
        $actions[] = array(
          'title' => t('Send message'),
          'url' => Url::fromRoute('optit.structure_keywords')
          //'href' => "admin/structure/optit/keywords/{$keyword_id}/interests/{$interest->get('id')}/subscriptions/message"
        );
      }


      $vars['rows'][] = array();

      $rows[] = array(
        $interest->get('id'),
        $interest->get('name'),
        $interest->get('description'),
        optit_time_convert($interest->get('created_at')),
        $interest->get('mobile_subscription_count'),
        $interest->get('status'),
        _optit_actions($actions),
      );
    }

    $build['table'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    );

//    // Initialize the pager
//    pager_default_initialize($optit->totalPages, 1);
//    $build['pager'] = [
//      '#theme' => 'pager',
//      '#route_name' => \Drupal::service('current_route_match')->getRouteName(),
//      '#quantity' => $optit->totalPages,
//      '#element' => 0,
//      '#parameters' => [],
//      '#tags' => [],
//    ];

    return $build;
  }
}
