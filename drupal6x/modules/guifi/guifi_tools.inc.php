<?php
/*
 * Created on 16/08/2008 by rroca
 *
 * functions for various tools
 */

function guifi_tools_ip_search($ipv4 = null) {
  $output = drupal_get_form('guifi_tools_ip_search_form',$ipv4);
  
  if (is_null($ipv4))
    return $output;
    
  $output .= '<h2>'.t('Query result for "ipv4 LIKE %ipv4"',
    array('%ipv4'=>"'".$ipv4."'")).'</h2>';

  $headers = array(t('id'),t('ipv4/mask'),t('interface'),t('device'),t('node'));
  $sqla = pager_query('SELECT * FROM {guifi_ipv4} WHERE ipv4 LIKE "'.$ipv4.'"',50);
  while ($ipv4 = db_fetch_object($sqla)) {
    $row = array();
    $row[] = $ipv4->id.'/'.$ipv4->interface_id;
    $row[] = $ipv4->ipv4.'/'.$ipv4->netmask;
    
    // interface
    if ($interface = db_fetch_object(db_query(
         'SELECT * from {guifi_interfaces} WHERE id=%d',
         $ipv4->interface_id))) {
      $row[] = $interface->id.'/'.$interface->radiodev_counter.' '.
        $interface->interface_type;     
    } else {
      $row[] = t('Orphan');
      $rows[] = $row;
      continue;
    }

    // device
    if ($device = db_fetch_object(db_query(
         'SELECT * from {guifi_devices} WHERE id=%d',
         $interface->device_id))) {
      $row[] = $device->id.'-'.
        l($device->nick,'guifi/device/'.$device->id);
    } else {
      $row[] = t('Orphan');
      $rows[] = $row;
      continue;
    }
    
    // node
    if ($node = db_fetch_object(db_query(
         'SELECT id from {guifi_location} WHERE id=%d',
         $device->nid))) {
      $node = node_load(array('nid'=>$node->id));
      $row[] = $node->id.'-'.
        l($node->title,'node/'.$node->id);
    } else {
      $row[] = t('Orphan');
      $rows[] = $row;
      continue;
    }

    $rows[] = $row;
  }
  
  $output .= theme('table',$headers,$rows);
  $output .= theme_pager(null, 50);
  return $output;
}

// IP search 
function guifi_tools_ip_search_form($form_state, $params = array()) {
    
  $form['ipv4'] = array(
    '#type' => 'textfield',
    '#title' => t('Network IPv4 address'),
    '#required' => true,
    '#default_value' => $params,
    '#size' => 16,
    '#maxlength' => 16,
    '#description' => t('Enter a valid ipv4 network address or pattern ' .
        'to get the related information available at the database for it.<br>' .
        'You can use valid SQL wilcards (%), for example, to query all the ' .
        'addresses begining with "10.138.0" you can use "10.138.0%"...'),
    '#weight' => 0,
  );
  $form['submit'] = array('#type' => 'submit','#value'=>t('Get information'));
  
  return $form;
}
 
function guifi_tools_ip_search_form_submit($form, &$form_state) {
   drupal_goto('guifi/menu/ip/ipsearch/'.$form_state['values']['ipv4']);
   return;    
}

// MAC Search
function guifi_tools_mac_search($mac = null) {
  $output = drupal_get_form('guifi_tools_mac_search_form',$mac);
  
  if (is_null($mac))
    return $output;
    
  $output .= '<h2>'.t('Query result for "ipv4 LIKE %ipv4"',
    array('%ipv4'=>"'".$mac."'")).'</h2>';

  $headers = array(t('mac'),t('interface'),t('device'),t('node'));
  $sqlm = pager_query('SELECT * FROM {guifi_interfaces} WHERE mac LIKE "'.$mac.'"',50);
  while ($interface = db_fetch_object($sqlm)) {
    $row = array();
    $row[] = $interface->mac;
    $row[] = $interface->id.'/'.$interface->radiodev_counter.' '.
      $interface->interface_type;     

    // device
    if ($device = db_fetch_object(db_query(
         'SELECT * from {guifi_devices} WHERE id=%d',
         $interface->device_id))) {
      $row[] = $device->id.'-'.
        l($device->nick,'guifi/device/'.$device->id);
    } else {
      $row[] = t('Orphan');
      $rows[] = $row;
      continue;
    }
    
    // node
    if ($node = db_fetch_object(db_query(
         'SELECT id from {guifi_location} WHERE id=%d',
         $device->nid))) {
      $node = node_load(array('nid'=>$node->id));
      $row[] = $node->id.'-'.
        l($node->title,'node/'.$node->id);
    } else {
      $row[] = t('Orphan');
      $rows[] = $row;
      continue;
    }

    $rows[] = $row;
  }
  
  $output .= theme('table',$headers,$rows);
  $output .= theme_pager(null, 50);
  return $output;
}
 
function guifi_tools_mac_search_form($form_state, $params = array()) {
    
  $form['mac'] = array(
    '#type' => 'textfield',
    '#title' => t('MAC address'),
    '#required' => true,
    '#default_value' => $params,
    '#size' => 20,
    '#maxlength' => 20,
    '#description' => t('Enter a valid MAC address or pattern ' .
        'to get the related information available at the database for it.<br>' .
        'You can use valid SQL wilcards (%), for example, to query all the MAC ' .
        'addresses begining with "00:0B" you can use "00:0B%"...'),
    '#weight' => 0,
  );
  $form['submit'] = array('#type' => 'submit','#value'=>t('Get information'));
  
  return $form;
}
 
function guifi_tools_mac_search_form_submit($form, &$form_state) {
   drupal_goto('guifi/menu/ip/macsearch/'.$form_state['values']['mac']);
   return;    
}

 
?>
