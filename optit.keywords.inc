<?php
/**
 * @file
 * Pages and forms related to keywords administration.
 */

/**
 * Page callback for the table with all keywords.
 *
 * @route("admin/structure/optit/keywords")
 * @return string
 * @throws Exception
 */
function optit_keywords_page() {

  // Initiate bridge class and dependencies and get the list of keywords from the API.
  $optit = optit_instantiate();
  $keywords = $optit->keywordsGet();

  // Start building vars for theme_table.
  $vars = array(
    'header' => array(
      t('ID'),
      t('Name'),
      t('Type'),
      t('Short code'),
      t('Status'),
      t('Subscription count'),
      t('Actions')
    ),
    'rows' => array()
  );

  // Iterate through received keywords and fill in table rows.
  foreach ($keywords as $keyword) {

    // Prepare links for actions column of the list.
    $actions = array();
    $actions[] = array(
      'title' => t('Edit'),
      'href' => "admin/structure/optit/keywords/{$keyword->get('id')}/edit"
    );
    $actions[] = array(
      'title' => t('View subscriptions'),
      'href' => "admin/structure/optit/keywords/{$keyword->get('id')}/subscriptions"
    );
    $actions[] = array(
      'title' => t('View interests'),
      'href' => "admin/structure/optit/keywords/{$keyword->get('id')}/interests"
    );
    if (module_exists('optit_send')) {
      $actions[] = array(
        'title' => t('Send message'),
        'href' => "admin/structure/optit/keywords/{$keyword->get('id')}/subscriptions/message"
      );
    }

    $vars['rows'][] = array(
      $keyword->get('id'),
      $keyword->get('keyword_name'),
      $keyword->get('keyword_type'),
      $keyword->get('short_code'),
      $keyword->get('status'),
      $keyword->get('mobile_subscription_count'),
      _optit_actions($actions),
    );
  }

  return theme('table', $vars);
}


/**
 * Form used both for creation and editing of keywords.
 *
 * @route("admin/structure/optit/keywords/new")
 * @route("admin/structure/optit/keywords/%/edit")
 * @param $form
 * @param $form_state
 * @param bool $id
 * @return array
 * @throws Exception
 */
function optit_keyword_edit_form($form, $form_state, $id = FALSE) {
  $optit = optit_instantiate();

  $form = array();

  // If submission was already tried, but it couldn't be done due to validation errors, we're instantiating Keyword entity from
  // submitted values.
  if (isset($form_state['input']['op'])) {
    $keyword = \Optit\Keyword::create($form_state['input']);
  }
  // If it is a first form edit load, we're loading keyword from the API.
  elseif ($id) {
    $keyword = $optit->keywordGet($id);
  }
  // If it is not an edit form, it must be a create form.
  else {
    $keyword = new \Optit\Keyword();
  }

  $form['#keyword_id'] = $id;

  _optit_form_select('billing_type', t('Billing type'), $keyword, $form);

  _optit_form_textfield('keyword_name', t('Keyword name'), $keyword, $form, array('#required' => TRUE));
  _optit_form_textfield('internal_name', t('Internal name'), $keyword, $form, array('#required' => TRUE));

  // @todo: Add interests once you create Interest entity.
  $form['interest_id'] = array(
    '#markup' => 'ADD INTEREST ID HERE ONCE YOU CREATE INTEREST ENTITY!'
  );
  _optit_form_select('welcome_msg_type', t('Welcome message type'), $keyword, $form);
  _optit_form_textfield('welcome_msg', t('Welcome message'), $keyword, $form, array(
    '#maxlength' => 93
  ));
  _optit_form_select('web_form_verification_msg_type', t('Web form verification message type'), $keyword, $form);
  _optit_form_textfield('web_form_verification_msg', t('Web form verification message'), $keyword, $form, array(
    '#maxlength' => 120
  ));
  _optit_form_select('already_subscribed_msg_type', t('Already subscribed message type'), $keyword, $form);
  _optit_form_textfield('already_subscribed_msg', t('Already subscribed message'), $keyword, $form, array(
    '#maxlength' => 120
  ));

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Submit')
  );

  //dsm($form);
  return $form;
}

function optit_keyword_edit_form_validate($form, &$form_state) {
  $optit = optit_instantiate();

  // Keyword must start with a letter.
  if (!preg_match('/^[A-Za-z]/', $form_state['values']['keyword_name'])) {
    form_set_error('keyword_name', t('Keyword name must start with a letter.'));
  }

  // Keyword must be longer than 4 characters.
  if (strlen($form_state['values']['keyword_name']) <= 4) {
    form_set_error('keyword_name', t('Keyword name must be longer than 4 characters.'));
  }

  // Keyword must contain only alphanumeric characters.
  if (!preg_match('/^[a-z0-9.]+$/i', $form_state['values']['keyword_name'])) {
    form_set_error('keyword_name', t('Keyword name must contain only alphanumeric characters.'));
  }

  // Keyword must be unique (if we are doing keyword CREATION.
  $keyword_exists = $optit->keywordExists($form_state['input']['keyword_name']);

  if (!$form['#keyword_id'] && $keyword_exists) {
    $form_state['error'] = TRUE;
    form_set_error('keyword_name', t('Name must be unique. Keyword with name :name already exists.', array(':name' => $form_state['values']['keyword_name'])));
  }

  $keyword = \Optit\Keyword::create($form_state['values']);

  $form_state['new_keyword'] = $keyword;

}

function optit_keyword_edit_form_submit($form, &$form_state) {
  $optit = optit_instantiate();

  if (!$form['#keyword_id']) {
    // @todo: Add success validation.
    $optit->keywordCreate($form_state['new_keyword']);
  }
  else {
    // @todo: Add success validation.
    $optit->keywordUpdate($form['#keyword_id'], $form_state['new_keyword']);
  }


  if (!isset($_GET['destination'])) {
    $form_state['redirect'] = "admin/structure/optit/keywords";
  }
}


function optit_keyword_message_form($form, $form_state, $keyword_id) {
  $form['keyword_id'] = array(
    '#value' => $keyword_id,
    '#type' => 'value'
  );

  $form['title'] = array(
    '#title' => t('Title'),
    '#description' => t('Please enter a title of message. This does not appear in the text message and is just used in the application as a short description of your message.'),
    '#type' => 'textfield',
    '#required' => TRUE,
  );

  $form['message'] = array(
    '#title' => t('Message'),
    '#description' => t('Please enter a text message. The message must be less than 160 characters including your keyword in the beginning of the message.'),
    '#type' => 'textarea',
    '#required' => TRUE,
  );
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Submit'),
  );
}


function _optit_form_select($name, $title, $entity, &$form, $options = array()) {
  $form[$name] = array(
      '#type' => 'select',
      '#title' => $title,
      '#value' => $entity->get($name),
      '#options' => $entity->allowedValues($name),
    ) + $options;
}

function _optit_form_textfield($name, $title, $entity, &$form, $options = array()) {
  $form[$name] = array(
      '#type' => 'textfield',
      '#title' => $title,
      '#value' => $entity->get($name),
    ) + $options;
}
