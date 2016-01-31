<?php

/**
 * Page callback for the table with all interests for the given keyword.
 *
 * @route("admin/structure/optit/members")
 */
function optit_members_list() {
  // Initiate bridge class and dependencies.
  $optit = optit_instantiate();

  // Decide page
  $page = (isset($_GET['page']) ? $_GET['page'] + 1 : 1);

  // Run query against the API.
  $entities = $optit->setPage($page)->membersGet();

  // Start building vars for theme_table.
  $vars = array(
    'header' => array(
      t('ID'),
      t('Name'),
      t('Carrier'),
      t('Phone'),
      t('Created at'),
      t('Status'),
      t('Actions')
    ),
    'rows' => array()
  );

  // Iterate through received interests and fill in table rows.
  foreach ($entities as $entity) {

    // Prepare links for actions column of the list.
    $actions = array();
    $actions[] = array(
      'title' => t('Unsubscribe from all keywords'),
      'href' => "admin/structure/optit/members/{$entity->get('phone')}/unsubscribe"
    );

    $vars['rows'][] = array(
      $entity->get('id'),
      $entity->get('first_name') . ' ' . $entity->get('last_name'),
      $entity->get('carrier_name'),
      $entity->get('phone'),
      $entity->get('created_at'),
      $entity->get('status'),
      _optit_actions($actions)
    );
  }

  // Initialize the pager
  pager_default_initialize($optit->totalPages, 1);
  return theme('table', $vars) . theme('pager', array('quantity' => $optit->totalPages));
}
