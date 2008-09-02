<?php
/*
 * Created on 1/08/2008 by rroca
 *
 * functions for tracroute tools
 */

function guifi_traceroute_search($params = null) {
  print_r($params);

  if (count($params)) {
    $to = explode(',',$params);
    $from = array_shift($to);
  }

  $output = drupal_get_form('guifi_traceroute_search_form',$from,$to);

  if (!count($to))
    return $output;

  (is_numeric($to[0])) ? $dto=guifi_get_devicename($to[0],'nick') : $dto=$to[0];


  $output .= '<h2>'.t('Software traceroute result from %from to %to',
    array('%from'=>guifi_get_devicename($from,'nick'),'%to'=>$dto)).'</h2>';

//  $headers = array(t('id'),
//    array('data'=>t('nipv4')),
//    t('mask'),t('interface'),t('device'),t('node'));
//  $sql = 'SELECT *,inet_aton(ipv4) AS nipv4 FROM {guifi_ipv4} WHERE ipv4 LIKE "'.$ipv4.'" ORDER BY inet_aton(ipv4)';
////  $sql .= tablesort_sql($header);
//  $sqla = pager_query($sql,50);
//  while ($ipv4 = db_fetch_object($sqla)) {
//    $row = array();
//    $row[] = $ipv4->id.'/'.$ipv4->interface_id;
//    $row[] = $ipv4->ipv4;
//    $row[] = $ipv4->netmask;
//
//    // interface
//    if ($interface = db_fetch_object(db_query(
//         'SELECT * from {guifi_interfaces} WHERE id=%d',
//         $ipv4->interface_id))) {
//      $row[] = $interface->id.'/'.$interface->radiodev_counter.' '.
//        $interface->interface_type;
//    } else {
//      $row[] = t('Orphan');
//      $rows[] = $row;
//      continue;
//    }
//
//    // device
//    if ($device = db_fetch_object(db_query(
//         'SELECT * from {guifi_devices} WHERE id=%d',
//         $interface->device_id))) {
//      $row[] = $device->id.'-'.
//        l($device->nick,'guifi/device/'.$device->id);
//    } else {
//      $row[] = t('Orphan');
//      $rows[] = $row;
//      continue;
//    }
//
//    // node
//    if ($node = db_fetch_object(db_query(
//         'SELECT id from {guifi_location} WHERE id=%d',
//         $device->nid))) {
//      $node = node_load(array('nid'=>$node->id));
//      $row[] = $node->id.'-'.
//        l($node->title,'node/'.$node->id);
//    } else {
//      $row[] = t('Orphan');
//      $rows[] = $row;
//      continue;
//    }
//
//    $rows[] = $row;
//  }
//
//  $output .= theme('table',$headers,$rows);
//  $output .= theme_pager(null, 50);
  return $output;
}

// IP search
function guifi_traceroute_search_form($form_state, $from = null, $to = array()) {

  $ftitle = t('From:');
  if ($from) {
    $fname = guifi_get_devicename($from,'large');
    $ftitle .= ' '.$fname;
  }

  $search_help = t('To find the device, you can write some letters to find the available devices in the database.');
  $form['from'] = array(
    '#type' => 'fieldset',
    '#title' => $ftitle,
    '#collapsible' => true,
    '#collapsed' => $from
  );
  $form['from']['from_description'] = array(
    '#type' => 'textfield',
    '#title' => t('Device'),
    '#required' => true,
    '#default_value' => $fname,
    '#size' => 60,
    '#maxlength' => 128,
    '#autocomplete_path'=> 'guifi/js/select-node-device',
    '#element_validate' => array('guifi_devicename_validate'),
    '#description' => t('Search for a device to trace the route from.').'<br>'.
        $search_help,
  );

  $dtitle = t('To:');
  if (count($to)) {
    if (is_numeric($to[0])) {
      $dname = guifi_get_devicename($to[0],'large');
      $dservice = '';
      $dtitle .= ' '.$dname;
    } else {
      $dservice = $to[0];
      $dname = '';
      $dtitle .= ' '.t('Explore service !service',array('!service'=>$to[0]));
    }
  }

  $form['to'] = array(
    '#type' => 'fieldset',
    '#title' => $dtitle,
    '#collapsible' => true,
    '#collapsed' => count($to)
  );
  $form['to']['to_description'] = array(
    '#type' => 'textfield',
    '#title' => t('Device'),
    '#default_value' => $dname,
    '#size' => 60,
    '#maxlength' => 128,
    '#element_validate' => array('guifi_devicename_validate'),
    '#autocomplete_path'=> 'guifi/js/select-node-device',
    '#description' => t('Target device to trace the route to.').'<br>'.
        $search_help,
    '#prefix'=>'<table><tr><td>',
    '#suffix'=>'</td>',
  );
  $types[] = t('<select one from this list>');
  $form['to']['discover_service'] = array(
    '#type' => 'select',
    '#title' => t("Service"),
    '#default_value' => $dservice,
    '#options' => array_merge($types,guifi_types('service')),
    '#description' => t('Type of service to be discovered'),
    '#prefix'=>'<td>',
    '#suffix'=>'</td>',
  );
  $form['submit'] = array(
    '#type' => 'submit',
    '#value'=>t('Get traceroute'),
    '#prefix'=>'<td>',
    '#suffix'=>'</td></tr></table>',
  );

  return $form;
}

function guifi_traceroute_search_form_validate($form, $form_state) {
  if ((!$form_state['values']['discover_service']) and
       ($form_state['values']['to_description'] == '')
     )
    form_set_error('discover_service', t('You must select either a service to discover or a device destination'));
  if (($form_state['values']['discover_service']) and
       ($form_state['values']['to_description'] != '')
     )
    form_set_error('to_description', t('You must select a service to discover or a device destination, not both'));
}

function guifi_traceroute_search_form_submit($form, &$form_state) {
   $from = explode('-',$form_state['values']['from_description']);
   ($form_state['values']['to_description'] != '') ?
     $to = explode('-',$form_state['values']['to_description'])
     : $to = array($form_state['values']['discover_service']);

   drupal_goto('guifi/menu/devel/traceroute/'.$from[0].','.$to[0]);
   return;
}

?>
