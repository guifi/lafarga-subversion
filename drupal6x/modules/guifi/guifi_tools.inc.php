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
  
  $output .= '<ul>';
  $output .= '<li>'.t('IP address %ip/%mask found with id %id at interface %interface',
    array('%ip'=>$ipv4->ipv4,
      '%mask'=>$ipv4->netmask,
      '%id'=>$ipv4->id,
      '%interface'=>$ipv4->interface_id
    )).'</li>';

  // interface
  if (!$interface = db_fetch_object(db_query(
        "SELECT * FROM {guifi_interfaces} WHERE id='%d'",
        $ipv4->interface_id))) {
    $output .= '<li>'.t('IP address is orphan').'</li>';
    if ($ipv4->netmask == '255.255.255.252')
      return '</ul>'.$output;
  }            
  $output .= '<li>'.t('Interface %id/%rc %type found at device %did/!dname',
    array('%id'=>$interface->id,
      '%rc'=>$interface->radiodev_counter,
      '%type'=>$interface->interface_type,
      '%did'=>$interface->device_id,
      '!dname'=>l(guifi_get_hostname($interface->device_id),
         'guifi/device/'.$interface->device_id)
    )).'</li>';
  
  // device
  if (!$device = db_fetch_object(db_query(
        "SELECT * FROM {guifi_devices} WHERE id='%d'",
        $interface->device_id))) {
    $output .= t('Interface is orphan');
    return $output;        
  }
  $output .= '<li>'.t('Device %id/!dname, %type found at node %nid/!nname',
    array('%id'=>$device->id,
      '!dname'=>l(guifi_get_hostname($device->id),
         'guifi/device/'.$device->id),
      '%type'=>$device->type,
      '%nid'=>$device->nid,
      '!nname'=>l(guifi_get_nodename($device->nid),
         'node/'.$device->nid)
    )).'</li>';   
     
       
  return '</ul>'.$output;
}
 
function guifi_tools_ip_search_form($form_state, $params = array()) {
    
  $form['ipv4'] = array(
    '#type' => 'textfield',
    '#title' => t('Network IPv4 address'),
    '#required' => true,
    '#default_value' => $params,
    '#size' => 16,
    '#maxlength' => 16,
    '#description' => t('Enter a valid ipv4 network address to get the related ' .
        'information available at the database for it.<br>' .
        'You can use valid SQL wilcards (%), for example, to query all the ' .
        'addresses begining with "10.138.0" you can use "10.138.0%"...'),
    '#weight' => 0,
  );
  $form['submit'] = array('#type' => 'submit','#value'=>t('Get information'));
  
  return $form;
}

function guifi_tools_ip_search_form_validate($form,$form_state) { 
  $item = _ipcalc($form_state['values']['ipv4'],'255.255.255.0');
  if ($item == -1) {
    form_set_error('ipv4', t('You must specify a valid ipv4 notation.'));
  }
}
 
function guifi_tools_ip_search_form_submit($form, &$form_state) {
   drupal_goto('guifi/menu/ip/search/'.$form_state['values']['ipv4']);
   return;    
}


 
?>
