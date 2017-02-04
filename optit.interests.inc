<?php
/**
 * @file
 * Pages and forms related to interest administration.
 */

/**
 * Page callback for the table with all interests for the given keyword.
 *
 * @route("admin/structure/optit/keywords/%/interests")
 */
function optit_interests_list($keyword_id) {

  // Initiate bridge class and dependencies and get the list of keywords from the API.
  $optit = optit_instantiate();
  $interests = $optit->interestsGet($keyword_id);

  // Start building vars for theme_table.
  $vars = array(
    'header' => array(
      t('ID'),
      t('Name'),
      t('Description'),
      t('Created at'),
      t('Number of subscriptions'),
      t('Status'),
      t('Actions')
    ),
    'rows' => array()
  );

  // Iterate through received interests and fill in table rows.
  foreach ($interests as $interest) {

    // Prepare links for actions column of the list.
    $actions = array();
    $actions[] = array(
      'title' => t('View subscriptions'),
      'href' => "admin/structure/optit/keywords/{$keyword_id}/interests/{$interest->get('id')}/subscriptions"
    );
    $actions[] = array(
      'title' => t('Subscribe a member'),
      'href' => "admin/structure/optit/keywords/{$keyword_id}/interests/{$interest->get('id')}/subscriptions/new"
    );
    if (module_exists('optit_send')) {
      $actions[] = array(
        'title' => t('Send message'),
        'href' => "admin/structure/optit/keywords/{$keyword_id}/interests/{$interest->get('id')}/subscriptions/message"
      );
    }

    $vars['rows'][] = array(
      $interest->get('id'),
      $interest->get('name'),
      $interest->get('description'),
      optit_time_convert($interest->get('created_at')),
      $interest->get('mobile_subscription_count'),
      $interest->get('status'),
      _optit_actions($actions),
    );
  }

  return theme('table', $vars);
}


/**
 * Form API implementation for creation of new interest.
 *
 * @route("admin/structure/optit/keywords/%/interests/new")
 */
function optit_interests_edit_form($form, $form_state, $keyword_id) {


  $form['keywordId'] = array(
    "#type" => 'value',
    "#value" => $keyword_id
  );

  $form['name'] = array(
    '#title' => t('Name'),
    '#description' => t('Name of the interest'),
    '#type' => 'textfield',
    '#required' => TRUE,
  );

  $form['description'] = array(
    '#title' => t('Description'),
    '#description' => t('Description of the interest'),
    '#type' => 'textfield',
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Submit')
  );

  return $form;
}

function optit_interests_edit_form_validate($form, &$form_state) {
  $optit = optit_instantiate();

  // Make sure there are not interests with the same name attached to the given keyword.
  $interests = $optit->interestsGet($form_state['values']['keywordId'], $form_state['values']['name']);
  if (count($interests) > 0) {
    form_set_error('name', t('There is already an interest with the given name. Please choose different name.'));
  }
}

function optit_interests_edit_form_submit($form, &$form_state) {
  $optit = optit_instantiate();
  if ($optit->interestCreate($form_state['values']['keywordId'], $form_state['values']['name'], $form_state['values']['description'])) {
    drupal_set_message(t('Interest successfully saved.'));
  }
  else {
    drupal_set_message(t('Interest could not be saved.', 'error'));
  }

  if (!isset($_GET['destination'])) {
    $form_state['redirect'] = "admin/structure/optit/keywords/{$form_state['values']['keywordId']}/interests";
  }
}
