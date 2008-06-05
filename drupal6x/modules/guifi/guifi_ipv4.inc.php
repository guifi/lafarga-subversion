<?php

/**
 * ipv4 editing functions
**/

/**
 * Form callback; handle the submit .
 */
function guifi_edit_ipv4_form_submit($form, &$form_state) {
  guifi_edit_ipv4_save($form_state['values']);
  $form_state['redirect'] = 'node/'.$form_state['values']['zone'].'/view/ipv4';
}

/**
 * Menu callback; handle the adding of a new guifi.
 */

function guifi_add_ipv4($zone) {
  drupal_set_title(t('Adding an ipv4 network range'));
  
  return drupal_get_form('guifi_edit_ipv4_form',array('add'=>$zone->nid));
} 

/**
 * Menu callback; delete a single ipv4 network.
 */
function guifi_delete_ipv4($id) {
  $result = db_query('SELECT base, mask, zone 
                      FROM {guifi_networks} 
                      WHERE id = %d', 
            $id);
  $guifi = db_fetch_object($result);

  if ($_POST['confirm']) {
    db_query('DELETE FROM {guifi_networks} WHERE id = %d', $id);
    drupal_set_message(t('Network range allocation %base/%mask deleted.',array('%base'=>$guifi->base,'%mask'=>$guifi->mask)));
    drupal_goto('node/'.$guifi->zone.'/view/ipv4');
  }
  return drupal_get_form('guifi_confirm_delete_ipv4',$guifi->base,$guifi->mask,$guifi->zone);
}

/**
 * Hook callback; delkete a network 
 */
function guifi_confirm_delete_ipv4($form_state,$base,$mask,$zone) {
  return confirm_form(array(), 
                     t('Are you sure you want to delete the network range %base/%mask?', array('%base' => $base,'%mask'=>$mask)),
                     'node/'.$zone.'/view/ipv4', 
                     t('This action cannot be undone.'), 
                     t('Delete'),
                     t('Cancel'));
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
  return drupal_get_form('guifi_edit_ipv4_form',array('edit'=>$id));
}

/**
 * Present the guifi zone editing form.
 */
function guifi_edit_ipv4_form($form_state, $params = array()) {

  if (empty($form_state['values'])) {
    // first execution, initializing the form
    
    // if new network, initialize the zone  
    if ($params['add'])
      $form_state['values']['zone'] = $params['add'];
      
    // if existent network, get the network and edit
    if ($params['edit']) 
      $form_state['values'] = db_fetch_array(db_query('SELECT * 
                                                       FROM {guifi_networks} 
                                                       WHERE id = %d', 
          $params['edit']));
  }    
  
  $form['base'] = array(
    '#type' => 'textfield',
    '#title' => t('Network base IPv4 address'),
    '#required' => true,
    '#default_value' => $form_state['values']['base'],
    '#size' => 16,
    '#maxlength' => 16,
    '#description' => t('A valid base ipv4 network address.'),
    '#weight' => 0,
  );
  $form['mask'] = array(
    '#type' => 'select',
    '#title' => t("Mask"),
    '#required' => true,
    '#default_value' => $form_state['values']['mask'],
    '#options' => guifi_types('netmask',24,0),
    '#description' => t('The mask of the network. The number of valid hosts of each masks is displayed in the list box.'),
    '#weight' => 1,
  );
  
  
  $form['zone'] = guifi_zone_select_field($form_state['values']['zone'],'zone');
  $form['zone']['#weight'] = 2;
 
  $form['network_type'] = array(
    '#type' => 'select',
    '#title' => t("Network type"),
    '#required' => true,
    '#default_value' => $form_state['values']['network_type'],
    '#options' => array('public'   => t('public - for any device available to everyone'),
                        'backbone' => t("backbone - used for internal management, links...")),
    '#description' => t('The type of usage that this network will be used for.'),
    '#weight' => 3,
  );
  $form['submit'] = array(
    '#type'=>'submit',
    '#value'=>t('Submit'),
    '#weight'=>4);
  $form['id'] = array(
    '#type'=>'hidden',
    '#value'=>$form_state['values']['id'],
    '#weight'=>5);
  $form['valid'] = array(
    '#type'=>'hidden',
    '#value'=>$form_state['values']['valid'],
    '#weight'=>6);

  return $form;
}

/**
 * Confirm that an edited guifi network has fields properly filled in.
 */
function guifi_edit_ipv4_form_validate($form,$form_state) {
  if (empty($form_state['values']['base'])) {
    form_set_error('base', t('You must specify a base network for the zone.'));
  }
  $item = _ipcalc($form_state['values']['base'],$form_state['values']['mask']);
  if ($item == -1) {
    form_set_error('base', t('You must specify a valid ipv4 notation.'));
  }
  if ( $form_state['values']['base'] != $item['netid']  ) {
    form_set_error('base', 
      t('You must specify a valid ipv4 network base address. Base address for:').
        $form_state['values']['base'].'/'.
        $form_state['values']['mask'].' '.
        t('is').' '.$item['netid'] );
  }
  if (empty($form_state['values']['id'])) {
    $result = db_query('SELECT base, mask 
                        FROM {guifi_networks} 
                        WHERE base = "%s" 
                         AND mask = "%s"',
        $form_state['values']['base'],
        $form_state['values']['mask']);
    if (db_affected_rows($result)>0) 
      form_set_error('base', t('Network already in use.'));
  }
}

/* outputs the network information data
**/
function guifi_ipv4_print_data($zone) {

  $rows = array();
  do {
    $result = db_query('SELECT 
                         n.id, n.base, n.mask, n.network_type 
                        FROM {guifi_networks} n 
                        WHERE n.valid = 1 
                         AND n.zone = "%s" 
                        ORDER BY n.base',
        $zone->id);
    while ($net = db_fetch_object($result)) {
      $item = _ipcalc($net->base,$net->mask);
      $row = array($zone->title,
                  $net->base.'/'.$item['maskbits'],
                  $net->mask,$item['netstart'],
                  $item['netend'],$item['hosts'],
                  $net->network_type);
      if (user_access('administar guifi networks'))
      $row = array_merge($row,
                  array(l(t('edit'),'guifi/ipv4/'.$net->id.'/edit').
                  ' '.
                  l(t('delete'),'guifi/ipv4/'.$net->id.'/delete')));
      $rows[] = $row;
    }
    $master = $zone->master;
    if ( $zone->master > 0)
      $zone = guifi_zone_load($zone->master);
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
    drupal_set_message(t('Updated guifi network %base.', array('%base' => $edit['base'])));
  }
  else {
    db_query("INSERT INTO {guifi_networks} ( zone, base, mask, network_type, timestamp_created, user_created) VALUES (%d, '%s', '%s', '%s', %d, %d)", $edit['zone'], $edit['base'], $edit['mask'], $edit['network_type'], time(), $user->uid);
    drupal_set_message(t('Created new guifi network %base.', array('%base' => $edit['base'])));
  }
}

/* guifi_ipv4_link_form(): edit an ipv4 within a link */

function guifi_ipv4_link_form(&$f,$ipv4,$interface,$tree,&$weight) {
  global $definedBridgeIpv4;

  if ($ipv4['deleted'])
    return 0;
    
  $ki = $tree[count($tree)-3];
  $ka = $tree[count($tree)-1];
  if (count($tree)>4)
    $rk = $tree[1];
  else
    $rk = null;
    
  if ($interface['interface_type'] == 'wLan/Lan')
    $bridge = true;
  if (($ipv4['netmask'] != '255.255.255.252')
    or (count($ipv4['links']) == 0))
  {
    // multilink set
    $multilink = TRUE;
    $f['local'] = array(
      '#type' => 'fieldset',
      '#parents' => $tree,
      '#title' => $ipv4['ipv4'].' / '.
        $ipv4['netmask'].' - '.
        (count($ipv4['links'])).' '.
        t('link(s)'),
      '#weight' => $weight++,
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#weight' => $weight++,
    );
    $prefix = '<table><tr><td>';
    $f['local']['id'] = array(
        '#type'=> 'hidden',
        '#parents'=>array_merge($tree,array('id')),
        '#default_value'=>$ipv4['id']);
    if (user_access('administer guifi networks')) {
      $f['local']['ipv4'] = array(
        '#type'=> 'textfield',
        '#parents'=>array_merge($tree,array('ipv4')),
        '#size'=> 16,
        '#maxlength'=>16,
        '#default_value'=>$ipv4['ipv4'],
        '#title'=>t('Local IPv4'),
        '#prefix'=> $prefix,
        '#suffix'=> '</td>',
        '#weight'=> 0,
      );
      $f['local']['netmask'] = array(
        '#type' => 'select',
        '#parents'=>array_merge($tree,array('netmask')),
        '#title' => t("Network mask"),
        '#default_value' => $ipv4['netmask'],
        '#options' => guifi_types('netmask',30,0),
        '#prefix'=> '<td>',
        '#suffix'=> '</td>',
        '#weight' =>1,
      );
    } else {
      $f['local']['ipv4'] = array(
        '#type' => 'item',
        '#parents'=>array_merge($tree,array('ipv4')),
        '#title' => t('Local IPv4'),
        '#value'=>  $ipv4['ipv4'],
        '#description'=> $ipv4['netmask'],
        '#prefix'=> $prefix,
        '#suffix'=> '</td>',
        '#weight' =>0,
      );
    }
  } else {
     // singlelink set
     $multilink = FALSE;
     $prefix = '<td>';
  }

  // foreach link
  if (count($ipv4['links'])) foreach($ipv4['links'] as $kl => $link)  {
    if ($link['deleted'])
      continue;
      
     // linked node-device
    guifi_link_form(
      $f['links'][$kl],
      $link,
      $ipv4,
      $tree,
      $multilink);

  } // foreach link

  // Deleting the IP address
  switch ($interface['interface_type']) {
  case 'wds/p2p':
    break;
  case 'wLan/Lan':
    if (!$definedBridgeIpv4) {
      $f['local']['delete_address'] = array(
        '#type' => 'item',
        '#parents'=>array_merge($tree,array('comment_address')),
        '#value'=>t('Main public address'),
        '#description' => t('wLan/Lan public IP address is required. No delete allowed.'),
        '#prefix'=> '<td>',
        '#suffix'=> '</td></tr></table>',
        '#description' => t('Can\'t delete this address. The device should have at least one public IP address.'),
        '#weight' =>  3,
      );
      $definedBridgeIpv4 = TRUE;
      break;
    }
  default:
    $f['local']['delete_address'] = array(
      '#type'=>'image_button',
      '#src'=>drupal_get_path('module', 'guifi').'/icons/drop.png',
      '#parents'=>array_merge($tree,array('delete_address')),
      '#attributes'=>array('title'=>t('Delete ipv4 address')), 
      '#submit' => array('guifi_ipv4_delete_submit'),
      '#prefix'=> '<td>',
      '#suffix'=> '</td></tr></table>',
      '#weight'=>3
      // parameters $rk, $ki, $ka, $ipv4, $netmask
    );
  }  // switch $it (interface_type)

  return count($ipv4['links']);
}

/* delete ipv4 */
/* _guifi_delete_ipv4(): Cofirmation dialog */
function _guifi_delete_ipv4(&$form,&$edit,$action) {
  $rk = $action[2]; // radio#
  $ki = $action[3]; // interface#
  $ka = $action[4]; // ipv4#
  guifi_log(GUIFILOG_TRACE,'function _guifi_delete_ipv4()',$action);

  if ($rk == '') {
    $ipv4 = $edit['interfaces'][$ki]['ipv4'][$ka];
  } else {
//     $ipv4 = $edit['radios'][$rk]['interfaces'][$ki]['ipv4'][$ka];
  }
  
  $fw = 0;
  guifi_form_hidden($form,$edit,$fw);
  
  $form['help'] = array(
    '#type' => 'item',
    '#title' => t('Are you sure that do you want to delete this address?'),
    '#value' => $ipv4['ipv4'].'/'.$ipv4['netmask'],
    '#weight' => $fw++,
  );
  
  drupal_set_title(t(
    'Delete address %ipv4/%mask',
    array('%ipv4'=>$ipv4['ipv4'],
      '%mask'=>$ipv4['netmask'])));
  _guifi_device_buttons($form,$action,$fw,TRUE);

  return FALSE;
}

/* _guifi_delete_ipv4_submit(): Action */

function guifi_ipv4_delete_submit(&$form,&$form_state) {
  $rk = $action[2]; // radio#
  $ki = $action[3]; // interface#
  $ka = $action[4]; // ipv4#

  guifi_log(GUIFILOG_TRACE,'function _guifi_delete_ipv4_submit()',$action);

  if ($rk == '')
    $ipv4 = &$edit['interfaces'][$ki]['ipv4'][$ka]['deleted'];
  else
    $ipv4 = &$edit['radios'][$rk]['interfaces'][$ki]['ipv4'][$ka]['deleted'];

  if ($ipv4['new'])
    unset($ipv4);
  else
    $ipv4['deleted'] = TRUE;

  return TRUE;
}

?>
