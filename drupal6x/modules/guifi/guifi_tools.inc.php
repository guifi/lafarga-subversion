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
    
  $output .= '<h2>'.t('Query result:').'</h2>';
  if (!$ipv4 = db_fetch_object(db_query(
        "SELECT * FROM {guifi_ipv4} WHERE ipv4='%s'",
        $ipv4))) {
     $output .= t('IP address is not currently used');    
     return $output;     
  }
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
        'information available at the database for it.'),
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
   drupal_set_message($form_state['values']['ipv4']);
   drupal_goto('guifi/menu/ip/search/'.$form_state['values']['ipv4']);
   return;    
}
 
?>
