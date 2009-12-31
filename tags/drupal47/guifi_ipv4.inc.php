<?php

/**
 * ipv4 editing functions
**/

/**
 * Menu callback; handle the adding of a new guifi.
 */
function guifi_add_ipv4() {
  $op = $_POST['op'];
  $edit = $_POST['edit'];
  $output = '';

  switch ($op) {
    case t('Submit'):
      guifi_edit_ipv4_validate($edit);
      if (!form_get_errors()) {
        guifi_edit_ipv4_save($edit);
        drupal_goto('admin/guifi');
      }
      // Fall through.
    default:
      $edit['valid'] = 1;
      $output .= guifi_edit_ipv4_form($edit);
  }

  print theme('page', $output);
}

/**
 * Menu callback; delete a single ipv4 network.
 */
function guifi_delete_ipv4($id) {
  $op = $_POST['op'];
  $result = db_query('SELECT base, mask, zone FROM {guifi_networks} WHERE id = %d', $id);
  $guifi = db_fetch_object($result);
  if (!$guifi) {
    drupal_goto('admin/guifi');
  }
  switch ($op) {
    case t('Delete'):
      db_query('DELETE FROM {guifi_networks} WHERE id = %d', $id);
      drupal_set_message(t('Network deleted.'));
      drupal_goto('node/'.$guifi->zone.'/view/ipv4');
      break;
    default:
      $message = t('Are you sure you want to delete the network %base?', array('%base' => theme('placeholder', $guifi->base)));
      $output = theme('confirm', $message, 'admin/guifi', t('This action cannot be undone.'), t('Delete'));
      print theme('page', $output);
  }
}

/**
 * Menu callback; hide a network 
 */
function guifi_disable_ipv4($id) {
  db_query('UPDATE {guifi_networks} SET valid = 0 WHERE id = %d', $id);
  drupal_set_message(t('guifi ipv4 disabled.'));
  drupal_goto('admin/guifi');
}

/**
 * Menu callback; dispatch to the appropriate guifi network edit function.
 */
function guifi_edit_ipv4($id = 0) {

  $op = $_POST['op'];
  $edit = $_POST['edit'];

  $output = '';
  $result = db_query('SELECT base, mask, zone FROM {guifi_networks} WHERE id = %d', $id);
  $guifi = db_fetch_object($result);

  switch ($op) {
    case t('Submit'):
      guifi_edit_ipv4_validate($edit);
      if (!form_get_errors()) {
        guifi_edit_ipv4_save($edit);
        drupal_goto('node/'.$guifi->zone.'/view/ipv4');
      }
      $output .= guifi_edit_ipv4_form($edit);
      break;
    default:
      if ($id > 0) {
   
        $item = guifi_get_ipv4($id);

        $edit['id'] = $item->id;
        $edit['zone'] = $item->zone;
        $edit['base'] = $item->base;
        $edit['mask'] = $item->mask;
        $edit['network_type'] = $item->network_type;
        $edit['valid'] = $item->valid;
        $edit['user_created'] = $item->user_created;
        $edit['user_changed'] = $item->user_changed;
        $edit['timestamp_created'] = $item->timestamp_created;
        $edit['timestamp_changed'] = $item->timestamp_changed;

      }
      else {
        $edit['id'] = 0; // In case a negative ID was passed in.
        $edit['zone'] = 1; // default to "ROOT" zone.
      }
      $output .= guifi_edit_ipv4_form($edit);
  }

  print theme('page', $output);
}

/**
 * Get network information 
**/
function guifi_get_ipv4($id) {

  $item = db_fetch_object(db_query('SELECT * FROM {guifi_networks} WHERE id = %d', $id));

  return $item;
}

/**
 * Present the guifi zone editing form.
 */
function guifi_edit_ipv4_form($edit) {

  $form .= form_textfield(t('Network base IPv4 address'), 'base', $edit['base'], 60, 128, t('A valid base ipv4 network address.'), NULL, TRUE);
  $form .= form_select(t('Network mask'), 'mask', $edit['mask'], guifi_types('netmask',24,0), t('The mask of the network. The number of valid hosts of each masks is displayed in the list box.') );
  $form .= form_select(t('Zone'), 'zone', $edit['zone'], guifi_zones_listbox(), t('The zone where this netwok belongs to.'));
  $form .= form_select(t('Network type'), 'network_type', $edit['network_type'], 
            array('public'   => t('public - for any device available to everyone'),
                  'backbone' => t("backbone - used for internal management, links...")),
            t('The type of usage that this network will be used for.'));

  $form .= form_submit(t('Submit'));

  $form .= form_hidden('id', $edit['id']);

  $form .= form_hidden('valid', $edit['valid']);

  return form($form);
}

/**
 * Confirm that an edited guifi network has fields properly filled in.
 */
function guifi_edit_ipv4_validate($edit) {
  if (empty($edit['base'])) {
    form_set_error('base', t('You must specify a name for the zone.'));
  }
  $item = _ipcalc($edit['base'],$edit['mask']);
  if ($item == -1) {
    form_set_error('base', t('You must specify a valid ipv4 notation.'));
  }
  if ( $edit['base'] != $item['netid']  ) {
    form_set_error('base', t('You must specify a valid ipv4 network base address. Base address for:').$edit['base'].'/'.$edit['mask'].' '.t('is').' '.$item['netid'] );
  }
  if (empty($edit['id'])) {
    $result = db_query('SELECT base, mask FROM {guifi_networks} WHERE base = "%s" AND mask = "%s"',$edit['base'],$edit['mask']);
    if (db_affected_rows($result)>0) 
      form_set_error('base', t('Network already in use.'));
  }
  
}

/**
 * outputs the network information data
**/
function guifi_ipv4_print_data($zone) {

  $rows = array();
  do {
    $result = db_query('SELECT n.id, n.base, n.mask, n.network_type FROM {guifi_networks} n WHERE n.valid = 1 AND n.zone = "%s" ORDER BY n.base',$zone->id);
    while ($net = db_fetch_object($result)) {
      $item = _ipcalc($net->base,$net->mask);
      $rows[] = array($zone->title,$net->base.'/'.$item['maskbits'],$net->mask,$item['netstart'],$item['netend'],$item['hosts'],$net->network_type,l(t('edit'),'admin/guifi/ipv4/edit/'.$net->id).' '.l(t('delete'),'admin/guifi/ipv4/delete/'.$net->id));
    }
    $master = $zone->master;
    if ( $zone->master > 0)
      $zone = guifi_get_zone($zone->master);
  } while ( $master  > 0);

  return array_merge($rows);
}


/**
 * Save changes to a guifi network into the database.
 */
function guifi_edit_ipv4_save($edit) {

  global $user;

  if ($edit['id']) {
    db_query("UPDATE {guifi_networks} SET zone = %d, base = '%s', mask = '%s', network_type = '%s', timestamp_changed = %d, user_changed = %d WHERE id = %d", $edit['zone'], $edit['base'], $edit['mask'], $edit['network_type'], time(), $user->uid, $edit['id']);
    drupal_set_message(t('Updated guifi network %base.', array('%base' => theme('placeholder', $edit['base']))));
  }
  else {
    db_query("INSERT INTO {guifi_networks} ( zone, base, mask, network_type, timestamp_created, user_created) VALUES (%d, '%s', '%s', '%s', %d, %d)", $edit['zone'], $edit['base'], $edit['mask'], $edit['network_type'], time(), $user->uid);
    drupal_set_message(t('Created new guifi network %base.', array('%base' => theme('placeholder', $edit['base']))));
  }
}

?>
