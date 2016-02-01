<?php
/**
 * @file
 * Pages and forms related to subscription administration.
 */

/**
 * Page: Table with all subscriptions for the given keyword.
 *
 * @route("admin/structure/optit/keywords/%/subscriptions")
 */
function optit_subscriptions_list($keyword_id) {
  // Initiate bridge class and dependencies.
  $optit = optit_instantiate();

  // Decide page
  $page = (isset($_GET['page']) ? $_GET['page'] + 1 : 1);

  // Run query against the API.
  $entities = $optit->setPage($page)
    ->subscriptionsGet($keyword_id);

  // Start building vars for theme_table.
  $vars = array(
    'header' => array(
      t('Member ID'),
      t('Phone'),
      t('Subscription type'),
      t('Signup date'),
      t('Created at'),
      t('Actions')
    ),
    'rows' => array()
  );

  // Iterate through received interests and fill in table rows.
  foreach ($entities as $entity) {
    // Prepare links for actions column of the list.
    $actions = array();
    $actions[] = array(
      'title' => t('Unsubscribe'),
      'href' => "admin/structure/optit/keywords/{$keyword_id}/subscriptions/{$entity->get('phone')}/unsubscribe"
    );
    $actions[] = array(
      'title' => t('Send message'),
      'href' => "admin/structure/optit/keywords/{$keyword_id}/subscriptions/message/{$entity->get('phone')}"
    );

    $vars['rows'][] = array(
      $entity->get('member_id'),
      $entity->get('phone'),
      $entity->get('type'),
      $entity->get('signup_date'),
      $entity->get('created_at'),
      _optit_actions($actions),
    );
  }

// Initialize the pager
  pager_default_initialize($optit->totalPages, 1);
  return theme('table', $vars) . theme('pager', array('quantity' => $optit->totalPages));
}


/**
 * Form: Creation of new subscription.
 *
 * @route("admin/structure/optit/keywords/%/subscriptions/new")
 */
function optit_subscriptions_edit_form($form, $form_state, $keyword_id) {
  $optit = optit_instantiate();

  $form['keywordId'] = array(
    "#type" => 'value',
    "#value" => $keyword_id
  );
  $form['phone'] = array(
    '#title' => t('Phone'),
    '#description' => t('Mobile phone number of the member with country code - 1 for U.S. phone numbers. (Phone or member id is required)  Example: 12225551212'),
    '#type' => 'textfield',
  );
  $form['member_id'] = array(
    '#title' => t('Member ID'),
    '#description' => t('Id number of the member (Phone or member id is required)'),
    '#type' => 'textfield',
  );

  $options = array();
  $interests = $optit->interestsGet($keyword_id);
  foreach ($interests as $interest) {
    $options[$interest->get('id')] = $interest->get('name');
  }

  if (count($options) > 0) {
    $form['interest_id'] = array(
      '#title' => t('Interests'),
      '#description' => t('THIS SHOULD BE A SELECT ELEMENT AND VALUES SHOULD BE LOADED FROM OPTIT!!'),
      '#type' => 'checkboxes',
      '#options' => $options,
    );
  }

  $form['first_name'] = array(
    '#title' => t('First name'),
    '#description' => t('First name of the member'),
    '#type' => 'textfield',
  );
  $form['last_name'] = array(
    '#title' => t('Last name'),
    '#description' => t('Last name of the member'),
    '#type' => 'textfield',
  );
  $form['address1'] = array(
    '#title' => t('Address 1'),
    '#type' => 'textfield',
  );
  $form['address2'] = array(
    '#title' => t('Address 2'),
    '#type' => 'textfield',
  );
  $form['city'] = array(
    '#title' => t('City'),
    '#type' => 'textfield',
  );
  $form['state'] = array(
    '#title' => t('State'),
    '#description' => t('Choose a state'),
    '#type' => 'select',
    '#options' => array(
        0 => t('- none -')
      ) + _optit_us_states(),
  );
  $form['zip'] = array(
    '#title' => t('Zip code'),
    '#type' => 'textfield',
  );
  $form['gender'] = array(
    '#title' => t('Gender'),
    '#type' => 'select',
    '#options' => array(
      0 => t('- none -'),
      'male' => t('Male'),
      'female' => t('Female')
    )
  );
  $form['birth_date'] = array(
    '#title' => t('Birth date'),
    '#type' => 'date',
  );
  $form['email_address'] = array(
    '#title' => t('Email address'),
    '#type' => 'textfield',
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Submit')
  );

  return $form;
}

function optit_subscriptions_edit_form_validate($form, &$form_state) {
  if (!$form_state['values']['member_id'] && !$form_state['values']['phone']) {
    form_set_error('member_id', t('Either member ID or phone must be provided.'));
    form_set_error('phone');
  }

  // @todo: validate email;

  // @todo: validate zip;

  // Iterate through interestID's and merge selected ones into a comma separated string.
  $interests = array();

  foreach ($form_state['values']['interest_id'] as $selectable) {
    if ($selectable) {
      $interests[] = $selectable;
    }
  }
  $form_state['values']['interest_id'] = implode(',', $interests);

  if (is_array($form_state['values']['birth_date'])) {
    $month = $form_state['values']['birth_date']['month'];
    $day = $form_state['values']['birth_date']['day'];
    $year = $form_state['values']['birth_date']['year'];
    if (strlen($month) == 1) {
      $month = '0' . $month;
    }
    if (strlen($day) == 1) {
      $day = '0' . $day;
    }
    $form_state['values']['birth_date'] = $year . $month . $day;
  }
}

function optit_subscriptions_edit_form_submit($form, &$form_state) {
  $optit = optit_instantiate();

  $v = $form_state['values'];

  if ($optit->subscriptionCreate($v['keywordId'], $v['phone'], $v['member_id'], $v['interest_id'], $v['first_name'], $v['last_name'], $v['address1'], $v['address2'], $v['city'], $v['state'], $v['zip'], $v['gender'], $v['birth_date'], $v['email_address'])) {
    if (!isset($_GET['destination'])) {
      $form_state['redirect'] = "admin/structure/optit/keywords/{$form_state['values']['keywordId']}/subscriptions";
    }
  }
  else {
    $form_state['rebuild'] = TRUE;
    $form_state['error'] = TRUE;
    drupal_set_message(t('Subscription could not be saved. Check error logs for details.'), 'error');
  }
}

/**
 * Confirmation form: Unsubscribe user from all keywords
 *
 * @route("admin/structure/optit/members/%/unsubscribe")
 */
function optit_subscription_cancel_all($form, $form_state, $phone) {
  $form['phone'] = array(
    '#type' => 'value',
    '#value' => $phone,
  );

  return confirm_form($form, t('Are you sure you want to unsubscribe phone number :phone from all keywords?', array(':phone' => $phone)), 'admin/structure/optit/members', t('This action cannot be undone.'), t('Unsubscribe'), t('Cancel'));
}


function optit_subscription_cancel_all_submit($form, &$form_state) {
  $optit = optit_instantiate();
  //@todo: handle exception if there is no member with given phone number.
  //@todo: handle exception if user does not have any subscriptions in a more elegant way.
  //@todo: Add success message.
  if (!$optit->subscriptionsCancelAllKeywords($form_state['values']['phone'])) {
    drupal_set_message(t(':phone could not be unsubscribed. Maybe it did not have any associated subscriptions.', array(":phone" => $form_state['values']['phone'])), 'warning');
  }
  if (!$_GET['destination']) {
    $form_state['redirect'] = "admin/structure/optit/members";
  }
}

/**
 * Confirmation form: Unsubscribe user from a given keyword
 *
 * @route("admin/structure/optit/keywords/%/subscriptions/%/unsubscribe")
 */
function optit_subscription_cancel_keyword($form, $form_state, $keyword_id, $phone) {
  $form['phone'] = array(
    '#type' => 'value',
    '#value' => $phone,
  );
  $form['keyword_id'] = array(
    '#type' => 'value',
    '#value' => $keyword_id,
  );

  return confirm_form($form, t('Are you sure you want to unsubscribe phone number :phone from keyword :keyword?', array(
    ':phone' => $phone,
    ':keyword' => $keyword_id
  )), 'admin/structure/optit/members', t('This action cannot be undone.'), t('Unsubscribe'), t('Cancel'));
}

function optit_subscription_cancel_keyword_submit($form, &$form_state) {
  $optit = optit_instantiate();
  //@todo: handle exception if there is no member with given phone number.
  //@todo: handle exception if user was not subscribed to given keyword in a more elegant way.
  //@todo: Add success message.
  if (!$optit->subscriptionCancelByKeyword($form_state['values']['phone'], $form_state['values']['keyword_id'])) {
    drupal_set_message(t(':phone could not be unsubscribed from :keyword.', array(
      ':phone' => $form_state['values']['phone'],
      ':keyword' => $form_state['values']['keyword_id']
    )), 'warning');
  }
  if (!$_GET['destination']) {
    $form_state['redirect'] = "admin/structure/optit/keywords/{$form_state['values']['keyword_id']}/subscriptions";
  }
}


/**
 * Page: Table with all subscriptions for the given interest.
 *
 * @route("admin/structure/optit/keywords/%/interests/%/unsubscribe")
 */
function optit_subscriptions_interest_list($keyword_id, $interest_id) {
  // Initiate bridge class and dependencies.
  $optit = optit_instantiate();

  // Decide page
  $page = (isset($_GET['page']) ? $_GET['page'] + 1 : 1);

  // Run query against the API.
  $entities = $optit->setPage($page)
    ->interestGetSubscriptions($interest_id);

  // Start building vars for theme_table.
  $vars = array(
    'header' => array(
      t('Member ID'),
      t('Phone'),
      t('Subscription type'),
      t('Signup date'),
      t('Created at'),
      t('Actions')
    ),
    'rows' => array()
  );

  // Iterate through received interests and fill in table rows.
  foreach ($entities as $entity) {
    // Prepare links for actions column of the list.
    $actions = array();
    $actions[] = array(
      'title' => t('Unsubscribe'),
      'href' => "admin/structure/optit/members/{$entity->get('phone')}/interests/{$interest_id}/unsubscribe"
    );

    $vars['rows'][] = array(
      $entity->get('member_id'),
      $entity->get('phone'),
      $entity->get('type'),
      $entity->get('signup_date'),
      $entity->get('created_at'),
      _optit_actions($actions),
    );
  }

  // Initialize the pager
  pager_default_initialize($optit->totalPages, 1);
  return theme('table', $vars) . theme('pager', array('quantity' => $optit->totalPages));
}

/**
 * Form: Subscribe a user to an interest
 * @route("admin/structure/optit/keywords/%/interests/%/subscription/new")
 */
function optit_subscription_subscribe_interest($form, $form_state, $keyword_id, $interest_id) {
  $optit = optit_instantiate();

  // First load all phone numbers subscribed to a given keyword.
  $keyword_subscriptions = $optit->subscriptionsGet($keyword_id);
  $phones = array();
  foreach ($keyword_subscriptions as $keyword_subscription) {
    $phones[$keyword_subscription->get('phone')] = $keyword_subscription->get('phone');
  }

  // Then remove all phone numbers that were already subscribed to a given interest.
  $interest_subscriptions = $optit->interestGetSubscriptions($interest_id);
  foreach ($interest_subscriptions as $interest_subscription) {
    unset($phones[$interest_subscription->get('phone')]);
  }

  // @todo: Handle exception when there are not phone numbers subscribed to the given keyword_id: redirect to keyword registration and set a nice message.

  $form['keyword_id'] = array(
    '#type' => 'value',
    '#value' => $keyword_id,
  );
  $form['interest_id'] = array(
    '#type' => 'value',
    '#value' => $interest_id,
  );
  $form['phone'] = array(
    '#title' => t('Phone number'),
    '#description' => t('The list contains phone numbers of members subscribed to a given keyword.'),
    '#type' => 'select',
    '#options' => $phones,
  );
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Submit')
  );

  return $form;
}

function optit_subscription_subscribe_interest_submit($form, &$form_state) {
  $optit = optit_instantiate();

  if ($optit->interestSubscribe($form_state['values']['interest_id'], $form_state['values']['phone'])) {
    drupal_set_message(t('Subscription saved successfully.'));
  }
  else {
    drupal_set_message(t(':phone could not be subscribed to :interest.', array(
      ':phone' => $form_state['values']['phone'],
      ':interest' => $form_state['values']['interest_id']
    )), 'warning');
  }

  if (!isset($_GET['destination'])) {
    // default redirection could be better...
    $form_state['redirect'] = "admin/structure/optit/keywords/{$form_state['values']['keyword_id']}/interests/{$form_state['values']['interest_id']}/subscriptions";
  }

}


/**
 * Confirmation form: Unsubscribe user from a given keyword
 *
 * @route("admin/structure/optit/members/%/interests/%/unsubscribe")
 */
function optit_subscription_cancel_interest($form, $form_state, $phone, $interest_id) {
  $form['phone'] = array(
    '#type' => 'value',
    '#value' => $phone,
  );
  $form['interest_id'] = array(
    '#type' => 'value',
    '#value' => $interest_id,
  );

  return confirm_form($form, t('Are you sure you want to unsubscribe phone number :phone from interest :interest?', array(
    ':phone' => $phone,
    ':interest' => $interest_id
  )), 'admin/structure/optit/members', t('This action cannot be undone.'), t('Unsubscribe'), t('Cancel'));
}

function optit_subscription_cancel_interest_submit($form, &$form_state) {
  $optit = optit_instantiate();
  //@todo: handle exception if there is no member with given phone number.
  //@todo: handle exception if user was not subscribed to given keyword in a more elegant way.
  //@todo: Add success message.
  if ($optit->interestUnsubscribe($form_state['values']['interest_id'], $form_state['values']['phone'])) {
    drupal_set_message(t('Subscription cancelled successfully.'));
  }
  else {
    drupal_set_message(t(':phone could not be unsubscribed from :interest.', array(
      ':phone' => $form_state['values']['phone'],
      ':interest' => $form_state['values']['interest_id']
    )), 'warning');
  }

  if (!isset($_GET['destination'])) {
    // default redirection could be better...
    $form_state['redirect'] = "admin/structure/optit/members";
  }
}

