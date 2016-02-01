<?php
/**
 * @file
 * The admin page.
 */

function optit_admin_page() {
  $form['credentials'] = array(
    '#type' => 'fieldset',
    '#title' => t('Credentials'),
//    '#group' => 'advanced',
  );
  $form['credentials']['optit_username'] = array(
    '#type' => 'textfield',
    '#title' => t('Username'),
    '#default_value' => variable_get('optit_username', ''),
    '#required' => TRUE,
  );

  // @todo: Secure password field!
  $form['credentials']['optit_password'] = array(
    '#type' => 'textfield',
    '#title' => t('Password'),
    '#default_value' => variable_get('optit_password', ''),
    '#required' => TRUE,
  );

  return system_settings_form($form);
}
