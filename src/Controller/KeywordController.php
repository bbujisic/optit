<?php

namespace Drupal\optit\Controller;

use Drupal;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\optit\Optit\Optit;
use Optit\Keyword;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the cart page.
 */
class KeywordController extends ControllerBase {

  /**
   * Returns the list of available keywords.
   */
  public function listPage() {
    $config = $this->config('optit.settings');

    // Initiate bridge class and dependencies and get the list of keywords from the API.
    $optit = new Optit($config->get('username'), $config->get('password'), OPTIT_URL);

    // Decide page
    $page = (isset($_GET['page']) ? $_GET['page'] + 1 : 1);

    // Run query against the API.
    $keywords = $optit->setPage($page)
      ->keywordsGet();


    $build = [];

    if (count($keywords) == 0) {
      $build['empty'] = [
        '#prefix' => '<div class="empty-page">',
        '#markup' => $this->t('Your keyword list is empty.'),
        '#suffix' => '</div>',
      ];
      return $build;
    }

    // Start building vars for theme_table.
    $header = array(
      t('ID'),
      t('Name'),
      t('Type'),
      t('Short code'),
      t('Status'),
      t('Subscription count'),
      t('Actions')
    );

    $rows = [];

    // Iterate through received keywords and fill in table rows.
    /** @var Keyword $keyword */
    foreach ($keywords as $keyword) {

      // Prepare links for actions column of the list.
      $actions = array();
      $actions[] = [
        'title' => $this->t('Edit'),
        'url' => Url::fromRoute('optit.structure_keywords')
        //"admin/structure/optit/keywords/{$keyword->get('id')}/edit"
      ];
      $actions[] = [
        'title' => $this->t('View subscriptions'),
        'url' => Url::fromRoute('optit.structure_keywords')
        //"admin/structure/optit/keywords/{$keyword->get('id')}/subscriptions"
      ];
      $actions[] = [
        'title' => $this->t('View interests'),
        'url' => Url::fromRoute('optit.structure_keywords')
        //"admin/structure/optit/keywords/{$keyword->get('id')}/interests"
      ];
      if (Drupal::moduleHandler()->moduleExists('optit_send')) {
        $actions[] = [
          'title' => $this->t('Send SMS'),
          'url' => Url::fromRoute('optit.structure_keywords')
          //"admin/structure/optit/keywords/{$keyword->get('id')}/subscriptions/sms"
        ];
        $actions[] = [
          'title' => $this->t('Send MMS'),
          'url' => Url::fromRoute('optit.structure_keywords')
          //"admin/structure/optit/keywords/{$keyword->get('id')}/subscriptions/mms"
        ];
      }

      $rows[] = array(
        $keyword->get('id'),
        $keyword->get('keyword_name'),
        $keyword->get('keyword_type'),
        $keyword->get('short_code'),
        $keyword->get('status'),
        $keyword->get('mobile_subscription_count'),
        _optit_actions($actions),
      );
    }

    $build['table'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    );

    // Initialize the pager
    pager_default_initialize($optit->totalPages, 1);
    $build['pager'] = [
      '#theme' => 'pager',
      '#route_name' => \Drupal::service('current_route_match')->getRouteName(),
      '#quantity' => $optit->totalPages,
      '#element' => 0,
      '#parameters' => [],
      '#tags' => [],
    ];

    return $build;
  }
}