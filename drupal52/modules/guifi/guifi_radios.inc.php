<?php

/* Radio edit forms & functions */
/* guifi_radio_form(): Main radio form (Common parameters)*/
function guifi_radio_form(&$edit) {
  global $hotspot;
  global $bridge;
  global $user;


  guifi_log(GUIFILOG_TRACE,'function guifi_radio_form()');
  unset($sidebar_left);

  $querymid = db_query("
    SELECT mid, model, f.nom manufacturer
    FROM guifi_model m, guifi_manufacturer f
    WHERE f.fid = m.fid
      AND supported='Yes'");
  while ($model = db_fetch_array($querymid)) {
     $models_array[$model["mid"]] = $model["manufacturer"] .", " .$model["model"];
  }

  $form['radio_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Device model, firmware & MAC address').' ('.$edit['variable']['firmware'].')',
    '#weight' => $form_weight++,
    '#collapsible' => TRUE,
    '#collapsed' => !is_null($edit['id']),
  );
  $form['radio_settings']['variable'] = array('#tree' => TRUE);
  $form['radio_settings']['variable']['model_id'] = array(
    '#type' => 'select',
    '#title' => t("Radio Model"),
    '#required' => TRUE,
    '#default_value' => $edit['variable']['model_id'],
    '#options' => $models_array,
    '#description' => t('Select the readio model that do you have.'),
    '#prefix' => '<table><tr><td>',
    '#suffix' => '</td>',
    '#weight' => 0,
  );
  $form['radio_settings']['variable']['firmware'] = array(
    '#type' => 'select',
    '#title' => t("Firmware"),
    '#required' => TRUE,
    '#default_value' => $edit['variable']['firmware'],
    '#options' => guifi_types('firmware'),
    '#description' => t('Used for automatic configuration.'),
    '#prefix' => '<td>',
    '#suffix' => '</td>',
    '#weight' => 1,
  );
  $form['radio_settings']['mac'] = array(
    '#type' => 'textfield',
    '#title' => t('Device MAC Address'),
    '#required' => FALSE,
    '#size' => 17,
    '#maxlength' => 17,
    '#default_value' => $edit['mac'],
    '#description' => t("Base/Main MAC Address.<br />Some configurations won't work if is blank"),
    '#prefix' => '<td>',
    '#suffix' => '</td></tr></table>',
    '#weight' => 2
  );

  $msg = t('Wireless radios settings').' - ';
  switch (count($edit['radios'])) {
  case 0: 
     $msg .= t('No radios');
     break;
  case 1:
     $msg .= t('1 radio');
     break;
  default:
     $msg .= count($edit['radios']).' '.t('radios');
  }
  $form['r'] = array(
    '#type' => 'fieldset',
    '#title' => $msg ,
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    '#weight' => $form_weight++,
  );
//  $form['r']['radios'] = array('#tree'=>TRUE);
  $rc = 0;
  $bridge = false;
  $cinterfaces = 0;
  $cipv4 = 0;
  $clinks = 0;
  if (!empty($edit['radios'])) foreach ($edit['radios'] as $key => $radio) {
    $hotspot = false;
    
    if ($radio['deleted']) continue;
    
    _guifi_radio_form($form,$radio,$key,$form_weight);

    $counters = guifi_radio_interfaces_form($edit, $form, $key, $form_weight);
    $form['r']['radios'][$key]['#title'] .= ' - '.
      $counters['interfaces'].' '.t('interface(s)').' - '.
      $counters['links'].' '.t('link(s)').' - '.
      $counters['ipv4'].' '.t('ipv4 address(es)');
    $cinterfaces += $counters['interfaces'];
    $cipv4 += $counters['ipv4'];
    $clinks += $counters['links'];

    // Going to paint the buttons

    // For AP Mode, clients_accepted
    if ($radio['mode'] == 'ap') { 
      // If no wLan interface, allow to create one
      if ((count($radio['interfaces']) < 2) or (user_access('administer guifi networks'))) {
        $form['r']['radios'][$key]['AddwLan'] = array(
          '#type'=>'submit',
          '#parents'=>array('radios',$key,'AddwLan'),
          '#value'=>t('Add wLan for clients'),
          '#name'=>'_action,_guifi_add_wlan,'.$key, 
          '#weight'=>$form_weight++);
      }
      if (!$hotspot) {
        $form['r']['radios'][$key]['AddHotspot'] = array(
          '#type'=>'submit',
          '#parents'=>array('radios',$key,'AddHotSpot'),
          '#value'=>t('Add Hotspot for guests'), 
          '#name'=>'_action,_guifi_add_hotspot,'.$key,
          '#weight'=>$form_weight++);
      }
    } else {
      // Mode Client or client-routed, allow to link to AP
      $cr = guifi_count_radio_links($radio);
      if ($cr['ap']==0)
        $form['r']['radios'][$key]['AddLink2AP'] = array(
          '#type'=>'submit',
          '#parents'=>array('radios',$key,'AddLink2AP'),
          '#value'=>t('Link to AP'), 
          '#name'=>'_action,_guifi_link_2ap,'.$key,
          '#weight'=>$form_weight++);
    }   

    // Only allow delete and move functions if the radio has been saved 
    if ($radio['new']==false)  {
      // Only allow delete radio if several when is not the first 
      if ((count($edit['radios'])==1) or ($key))
      $form['r']['radios'][$key]['delete'] = array(
        '#type'=>'submit',
        '#parents'=>array('radios',$key,'delete'),
        '#value'=>t('Delete radio'), 
        '#name'=>'_action,_guifi_delete_radio,'.$key,
        '#weight'=>$form_weight++);
      $form['r']['radios'][$key]['change'] = array(
        '#type'=>'submit','#value'=>t('Move to another device'), 
        '#parents'=>array('radios',$key,'change'),
        '#name'=>'_action,_guifi_move_radio'.$key,
        '#weight'=>$form_weight++);
    }

    // if not first, allow to move up  
    if ($rc) 
      $form['r']['radios'][$key]['up'] = array(
        '#type'=>'submit','#value'=>t('Up'), 
        '#parents'=>array('radios',$key,'up'),
        '#name'=>'_action,_guifi_move_radio_updown,'.$key.','.($key-1),
        '#weight'=>$form_weight++);
    // if not last, allow to move down
    if (($rc+1) < count($edit['radios'])) 
      $form['r']['radios'][$key]['down'] = array(
        '#type'=>'submit','#value'=>t('Down'), 
        '#parents'=>array('radios',$key,'down'),
        '#name'=>'_action,_guifi_move_radio_updown,'.$key.','.($key+1),
        '#weight'=>$form_weight++);

    $rc++;
  } // foreach radio

  // Edit radio form or add new radio
  $cr = 0; $tr = 0; $firewall=false;
  $maxradios = db_fetch_object(db_query('SELECT radiodev_max FROM {guifi_model} WHERE mid=%d',$edit[variable][model_id]));
//    print "Max radios: ".$maxradios->radiodev_max." \n<br />";
  if (isset($edit[radios])) 
  foreach ($edit[radios] as $k=>$radio) {
    $tr++;
    if (!$radio['deleted'])
      $cr++;
    if ($radio['mode'] == 'client') 
      $firewall = true;
  } // foreach $radio

//   print "Max radios: ".$maxradios->radiodev_max." Current: $cr Total: $tr Firewall: $firewall Edit details: $edit[edit_details]\n<br />";
  $modes_arr = guifi_types('mode');
//  print_r($modes_arr);
 
  if ($cr>0)
    if (!$firewall)
      $modes_arr = array_diff_key($modes_arr,array('client'=>0));
    else
      $modes_arr = array_intersect_key($modes_arr,array('client'=>0));
  if ($cr < $maxradios->radiodev_max) {
    if ( (( $edit['id'] > 0 ) && (!isset($edit[edit_details]))) and ($tr < $maxradios->radiodev_max)) {
//      print "Max radios: ".$maxradios->radiodev_max." Current: $cr Total: $tr Firewall: $firewall Edit details: $edit[edit_details]\n<br />";
      $form['r']['newradio_mode'] = array(
        '#type' => 'select',
        '#parents' => array('r','newradio_mode'),
        '#required' => FALSE,
        '#default_value' =>  'client',
        '#options' => $modes_arr,
        '#prefix' => '<table  style="width: 100%"><th colspan="0">'.t('New radio (mode)').'</th><tr><td  style="width: 0" align="right">',
        '#suffix' => '</td>',
        '#weight' => 20); 
      $form['r']['AddRadio'] = array(
        '#type' => 'button',
        '#parents' => array('r','AddRadio'),
        '#value' => t('Add new radio'),
        '#name'=>'_action,_guifi_add_radio',
        '#prefix' => '<td style="width: 10em" align="left">',
        '#suffix' => '</td><td style="width: 100%" align="right">&nbsp</td></tr>',
        '#weight' => 21,
       );
       $form['r']['help_addradio'] = array(
        '#type' => 'item',
        '#description' => t('Usage:<br />Choose <strong>wireless client</strong> mode for a normal station with full access to the network. That\'s the right choice in general.<br />Use the other available options only for the appropiate cases and being sure of what you are doing and what does it means. Note that you might require to be authorized by networks administrators for doing this.<br />Youwill not be able to define you link and get connected to the network until you add at least one radio.'),
        '#prefix' => '<tr><td colspan="0">',
        '#suffix' => '</td></tr></table>',
        '#weight' => 22,
       );
      
    } else {
      $form['r']['AddRadio'] = array(
        '#type' => 'item',
        '#value' => t('You can add radios to this device once has been saved into de database'),
        '#weight' => $form_weight++,
       );
    }
  }
  $form['r']['#title'] .= ' - '.
    $cinterfaces.' '.t('interface(s)').' - '.
    $cipv4.' '.t('address(es)').' - '.
    $clinks.' '.t('link(s)');

  return $form;
}

/* _guifi_radio_form(): radio (loop per radio) form */
function _guifi_radio_form(&$form, $radio, $key, &$form_weight = -200) {


    if ($radio['new']) {
//      drupal_set_message(t('Adding a new radio, setting the partameters.').
//                     '<br>'.t('Mode:').' '.$radio['mode'].' #'.$key.' '.$form_weight.
//                     '&nbsp'); 
      $collapsed = false;
    } else
      $collapsed = true;

    $fw2 = 0;

    $form['r']['radios'][$key] = array(
      '#type' => 'fieldset',
      '#title' => t('Radio #').$key.' - '.$radio['mode'].' - '.$radio['ssid'],
      '#collapsible' => $collapsed,
      '#collapsed' => $collapsed,
      '#tree'=> TRUE,
      '#weight' => $form_weight++,
    );
    if ($radio['mode'] == 'ap')
      $form['r']['radios'][$key]['ssid'] = array(     
        '#type' => 'textfield',
        '#parents'=> array('radios',$key,'ssid'),
        '#title' => t('SSID'),
        '#required' => TRUE,
        '#size' => 30,
        '#maxlength' => 30,
        '#default_value' => $radio['ssid'],
        '#description' => t("SSID to identify this radio signal."),
        '#prefix'=>'<table><tr><td>',
        '#suffix'=>'</td>',
        '#weight' => $fw2++
      );
    else {
      $inherit_msg = t('Will take it from the connected AP.');
      $form['r']['radios'][$key]['ssid'] = array(     
        '#type' => 'hidden',
        '#parents'=> array('radios',$key,'ssid'),
        '#title' => t('SSID'),
        '#value' => $radio['ssid'],
        '#description' => $inherit_msg,
        '#prefix'=>'<table><tr><td>',
        '#suffix'=>'</td>',
        '#weight' => $fw2++
      );
    }
    $form['r']['radios'][$key]['mac'] = array(
      '#type' => 'textfield',
      '#parents'=> array('radios',$key,'mac'),
      '#title' => t('MAC'),
      '#size' => 17,
      '#maxlength' => 17,
      '#default_value' => $radio['mac'],
      '#description' => t("Wireless MAC Address.<br />Some configurations won't work if is blank"),
      '#prefix'=>'<td>',
      '#suffix'=>'</td></tr>',
      '#weight' => $fw2++
    );
    if ($radio['mode'] == 'ap') {
      $form['r']['radios'][$key]['protocol'] = array(
        '#type' => 'select',
        '#parents'=> array('radios',$key,'protocol'),
        '#title' => t("Protocol"),
        '#required' => TRUE,
        '#default_value' =>  $radio["protocol"],
        '#options' => guifi_types('protocol'),
        '#description' => t('Select the protocol where this radio will operate.'),
        '#prefix'=>'<tr><td>',
        '#suffix'=>'</td>',
        '#weight' => $fw2++,   
      );
      $form['r']['radios'][$key]['channel'] = array(
        '#type' => 'select',
        '#parents'=> array('radios',$key,'channel'),
        '#title' => t("Channel"),
        '#required' => TRUE,
        '#default_value' =>  $radio["channel"],
        '#options' => guifi_types('channel',null,null,$radio['protocol']), 
        '#description' => t('Select the channel where this radio will operate.'),
        '#prefix'=>'<td>',
        '#suffix'=>'</td>',
        '#weight' => $fw2++,   
      );
      $form['r']['radios'][$key]['clients_accepted'] = array(
        '#type' => 'select',
        '#parents'=> array('radios',$key,'clients_accepted'),
        '#title' => t("Clients accepted?"),
        '#required' => TRUE,
        '#default_value' =>  $radio["clients_accepted"],
        '#options' => drupal_map_assoc(array( 0=>'Yes',1=>'No')),
        '#description' => t('Do this radio accept connections from clients?'),
        '#prefix'=>'<td>',
        '#suffix'=>'</td></tr></table>',
        '#weight' => $fw2++,   
      );
     } else {
      $form['r']['radios'][$key]['protocol'] = array(
        '#type' => 'hidden',
        '#parents'=> array('radios',$key,'protocol'),
        '#title' => t("Protocol"),
        '#value' =>  $radio["protocol"],
        '#description' => $inherit_msg,
        '#prefix'=>'<tr><td>',
        '#suffix'=>'</td>',
        '#weight' => $fw2++,   
      );
      $form['r']['radios'][$key]['channel'] = array(
        '#type' => 'hidden',
        '#parents'=> array('radios',$key,'channel'),
        '#title' => t("Channel"),
        '#value' =>  $radio["channel"],
        '#options' => guifi_types('channel',null,null,$radio['protocol']), 
        '#description' => $inherit_msg,
        '#prefix'=>'<td>',
        '#suffix'=>'</td>',
        '#weight' => $fw2++,   
      );
      $form['r']['radios'][$key]['clients_accepted'] = array(
        '#type' => 'hidden',
        '#parents'=> array('radios',$key,'clients_accepted'),
        '#value' =>  $radio["clients_accepted"],
        '#weight' => $fw2++,   
        '#prefix'=>'<td>',
        '#suffix'=>'</td></tr></table>',
      ); 
    }

    // Antenna settings group
    $form['r']['radios'][$key]['antenna'] = array(
      '#type' => 'fieldset',
      '#title' => t('Antenna settings'),
      '#collapsible' => $collapsed,
      '#collapsed' => $collapsed,
      '#weight' => $fw2++,
    ); 
    $fw2 = 0;
    $form['r']['radios'][$key]['antenna']['antenna_angle'] = array(
      '#parents' => array('radios',$key,'antenna_angle'),
      '#type' => 'select',
      '#title' => t("Type (angle)"),
      '#default_value' =>  $radio["antenna_angle"],
      '#options' => guifi_types('antenna'),
      '#description' => t('Angle (depends on the type of antena you will use)'),
      '#prefix'=>'<table><tr><td>',
      '#suffix'=>'</td>',
      '#weight' => $fw2++,
    );
    $form['r']['radios'][$key]['antenna']['antenna_gain'] = array(
      '#parents' => array('radios',$key,'antenna_gain'),
      '#type' => 'select',
      '#title' => t("Antenna gain"),
      '#default_value' =>  $radio["antenna_gain"],
      '#options' => drupal_map_assoc(array(2,8,12,14,18,21,24,'more')),
      '#description' => t('db gain'),
      '#prefix'=>'<td>',
      '#suffix'=>'</td>',
      '#weight' => $fw2++,
    );
    $form['r']['radios'][$key]['antenna']['antenna_azimuth'] = array(
      '#parents' => array('radios',$key,'antenna_azimuth'),
      '#type' => 'textfield',
      '#title' => t('Degrees (º)'),
      '#size' => 3,
      '#maxlength' => 3,
      '#default_value' => $radio['antenna_azimuth'],
      '#description' => t('Azimuth in degrees')." (0-360º)",
      '#prefix'=>'<td>',
      '#suffix'=>'</td></tr></table>',
      '#weight' => $fw2++,
    );
}

/* guifi_radio_interfaces_form(): Tadio interfaces form */
function guifi_radio_interfaces_form(&$edit, &$form, $rk, &$weight) {
  global $hotspot;
  global $bridge;

  guifi_log(GUIFILOG_TRACE,sprintf('function guifi_radio_interfaces_form(key=%d)',$rk)); 
  $f = array();

  if (count($edit['radios'][$rk]['interfaces']) == 0)
    return $weight;

  $interfaces_count = 0;
  $ipv4_count = 0;
  $links_count = array();

  unset($ilist);
  foreach ($edit['radios'][$rk]['interfaces'] as $ki => $interface) {
//    guifi_log(GUIFILOG_FULL,'interface',$interface); 
    if ($interface['interface_type'] == null)
      continue;

    $interfaces_count++;

    $it = $interface['interface_type'];
    $ilist[$it] = $ki;

    if (count($interface['ipv4']) > 0)
    foreach ($interface['ipv4'] as $ka => $ipv4) {

      $ipv4_count++;
      
      $links_count[$it] += guifi_link_ipv4_form(
        $f[$it][$ki]['ipv4'][$ka],
        $ipv4,
        $interface,
        array('radios',$rk,'interfaces',$ki,'ipv4',$ka),
        $weight);

    }   // foreach ipv4
    switch ($it) {
    case 'HotSpot':
      $f[$it][$ki]['ipv4'][$ka]['local']['deleteHotspot'] = array(
        '#type'=>'button',
        '#parents'=>array('radios',$rk,'interfaces',$ki,'deleteHotspot'),
        '#value'=>t('Delete Hotspot'), 
        '#name'=>'_action,_guifi_delete_radio_interface,'.$rk.','.$ki,
        '#weight'=>4);
      $hotspot = true;
      break;
    case 'wds/p2p':
      $f[$it][$ki]['ipv4'][$ka]['local']['AddWDS'] = array(
        '#type'=>'button',
        '#parents'=>array('radios',$rk,'interfaces',$ki,'AddWDS'),
        '#value'=>t('Add WDS/bridge p2p link'), 
        '#name'=>'_action,_guifi_add_wds,'.$rk.','.$ki,
        '#weight'=>4);
      break;
    }

  }    // foreach interface

  foreach ($f as $it => $value) {
    //    guifi_log(GUIFILOG_FULL,'building form for: ',$value);
    switch ($it) {
    case 'wLan/Lan':
    case 'wds/p2p':
      $title = $it.' - '.$links_count[$it].' '.t('link(s)');
      break;
    case 'wLan':
      $title = $it.' - '.
        count($value).' '.t('interface(s)').' - '.
        $links_count[$it].' '.t('link(s)');
      break;
    default:
      $title = $it;
    }
    $form['r']['radios'][$rk][$it] = array(
    '#type' => 'fieldset',
    '#title' => $title,
    '#weight' => $weight,
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    );
    $weight++;

    if (!empty($value))
      foreach ($value as $ki => $fin)
        $form['r']['radios'][$rk][$it]['interfaces'][$ki] = $fin; 
    else {
      if ((!$edit['radios'][$rk]['interfaces'][$ilist[$it]]['new']) and 
        ($it != 'wds/p2p') and 
        ($it != 'wLan/Lan'))
        $form['r']['radios'][$rk][$it]['delete_address'] = array(
          '#type' => 'button',
          '#parents'=>array('radios',$rk,'interfaces',$ilist[$it],'delete_interface'),
          '#value'=>t('Delete'),
          '#name'=>implode(',',array(
               '_action',
               '_guifi_delete_radio_interface',
               $rk,$ilist[$it]
               )),
          '#weight' => $weight++,
        );
    }
 }
 
 return array('interfaces'=>$interfaces_count,'ipv4'=>$ipv4_count,'links'=>array_sum($links_count));
}

/* guifi_radio_validate()): Validate radio, called as a hook while validating the form */
function guifi_radio_validate($edit) {
  guifi_log(GUIFILOG_TRACE,"function _guifi_radio_validate()");

  if (!(empty($edit['mac']))) { 
    $mac = _guifi_validate_mac($edit['mac']);
    if ($mac) {
      $edit['mac'] = $mac;
    } else {
      form_set_error(
        'mac',
        t('Error in MAC address, use 00:00:00:00:00:00 format.').' '.$edit['mac']);
    }
  }

  if (($edit['variable']['firmware'] != 'n/a') and
    ($edit['variable']['firmware'] != null)) {
    $radio = db_fetch_object(db_query("
      SELECT model
      FROM {guifi_model}
      WHERE mid='%d'",
      $edit['variable']['model_id']));
    if (!guifi_type_relation(
      'firmware',
      $edit['variable']['firmware'],
      $radio->model)) {
      form_set_error('variable][firmware',
        t('This firmware with this radio model is NOT supported.'));
    } 
  }

}

/* Swapping rdios (move up & down) */
/* _guifi_move_radio_updown(): Confirmation dialog */
function _guifi_move_radio_updown(&$form,&$edit,$action) {
  $old=$action[2];
  $new=$action[3];

  guifi_log(GUIFILOG_TRACE,
    sprintf('function _guifi_move_radio_updown(%d,%d)',
    $old,
    $new));
    
  $fw = 0;
  guifi_form_hidden($form,$edit,$fw);
  $form['help'] = array(
    '#type' => 'item',
    '#title' => t('Are you sure you want to swap this radios?'),
    '#value' => '#'.$old.'-'.$edit['radios'][$old]['ssid'].' <-> '.
	'#'.$new.'-'.$edit['radios'][$new]['ssid'],
    '#description' => t('If you save at this point, this radios will be swapped and the device saved.'),
    '#weight' => 0,
  );
  drupal_set_title(t('Swap radios'));
  _guifi_device_buttons($form,$action);

  return FALSE;
}

/* _guifi_move_radio_updown_submit(): Action */
function _guifi_move_radio_updown_submit(&$edit,$action) {
  $old = $action[2];
  $new = $action[3];

  guifi_log(GUIFILOG_TRACE,sprintf('function _guifi_move_radio_updown_submit(%d,%d)',$old,$new));

  $old_radio = $edit['radios'][$old];
  unset($edit['radios'][$old]);
  $new_radio = $edit['radios'][$new];
  unset($edit['radios'][$new]);
  $edit['radios'][$new] = $old_radio;
  $edit['radios'][$old] = $new_radio;

  ksort($edit['radios']);
}

/* Add wlan */
/* _guifi_add_wlan(): Cofirmation dialog */
function _guifi_add_wlan(&$form,&$edit,$action) {
  $radio = $action[2];
  guifi_log(GUIFILOG_TRACE,sprintf('function _guifi_add_wlan(%d)',$radio));
  $fw = 0;
  guifi_form_hidden($form,$edit,$fw);
  $form['help'] = array(
    '#type' => 'item',
    '#title' => t('Are you sure you want to create a new wLan interface for clients at this radio?'),
    '#value' => t('Radio').' #'.$radio.'-'.$edit['radios'][$radio]['ssid'],
    '#description' => t('If you save at this point, this interface will be created and device saved.'),
    '#weight' => 0,
  );
  drupal_set_title(t('Create a wLan interface at %name',array('%name'=>$edit['radios'][$radio]['ssid'])));
  _guifi_device_buttons($form,$action);

  return FALSE;
}

/* _guifi_add_wlan_submit(): Action */
function _guifi_add_wlan_submit(&$edit,$action) {
  $radio = $action[2];
  guifi_log(GUIFILOG_TRACE,sprintf('function _guifi_add_wlan_submit(%d)',$radio));


  $interface = array();
  $interface['new']=true;
  $ips_allocated=guifi_get_ips('0.0.0.0','0.0.0.0',$edit);
  //   print_r($ips_allocated);
  $net = guifi_get_subnet_by_nid($edit['nid'],'255.255.255.224','public',$ips_allocated);
  guifi_log(GUIFULOG_FULL,"IPs allocated: ".count($ips_allocated)." Obtained new net: ".$net."/27");
  $interface['ipv4'][$radio]=array();
  $interface['ipv4'][$radio]['new']=true;
  $interface['ipv4'][$radio]['ipv4']=guifi_ip_op($net);
  guifi_log(GUIFILOG_FULL,"assigned IPv4: ".$edit['radios'][$radio]['interfaces'][$interface_id]['ipv4'][$radio]['ipv4']);
  $interface['ipv4'][$radio]['netmask']='255.255.255.224';
  $interface['ipv4'][$radio]['links']=array();
  $interface['interface_type']='wLan';
  $edit['radios'][$radio]['interfaces'][]=$interface;
  
  return TRUE;
}

/* Add Hotspot */
function _guifi_add_hotspot(&$form,&$edit,$action) {
  $radio = $action[2];
  guifi_log(GUIFILOG_TRACE,sprintf('function _guifi_add_hotspot(%d)',$radio));
  $fw = 0;
  guifi_form_hidden($form,$edit,$fw);
  $form['help'] = array(
    '#type' => 'item',
    '#title' => t('Are you sure you want to create a Hotspot interface for guests at this radio?'),
    '#value' => t('Radio').' #'.$radio.'-'.$edit['radios'][$radio]['ssid'],
    '#description' => t('If you save at this point, this interface will be created and device saved.'),
    '#weight' => 0,
  );
  drupal_set_title(t('Create a Hotspot interface at %name',array('%name'=>$edit['radios'][$radio]['ssid'])));
  _guifi_device_buttons($form,$action);

  return FALSE;
}

function _guifi_add_hotspot_submit(&$edit,$action) {
  $radio = $action[2];
  guifi_log(GUIFILOG_TRACE,sprintf('function _guifi_add_hotspot_submit(%d)',$$radio));

  // obtaining a public IP fot the hotspot NAT

  // filling variables
  $interface=array();
  $interface['new']=true;
  $interface['interface_type']='HotSpot';
  $edit['radios'][$radio]['interfaces'][] = $interface;
  
  return TRUE;
}

/* Add  aradio to the device */
function _guifi_add_radio(&$form,&$edit,$action) {
  guifi_log(GUIFILOG_TRACE, "function _guifi_add_radio()");

  // wrong form navigation, can't do anything
  if ($edit['r']['newradio_mode'] == null)
    return TRUE;


  // next id
  $rc = 0; // Radio radiodev_counter next pointer
  $tc = 0; // Total active radios

  // fills $rc & $tc proper values
  if (isset($edit[radios])) foreach ($edit['radios'] as $k=>$r) 
    if ($k+1 > $rc)  {
      $rc = $k+1;
      if (!$edit['radios'][$k][delete])
        $tc++;
    }

    $node=node_load(array('nid'=>$edit['nid']));
    $zone=node_load(array('nid'=>$node->zone_id));
    if (($zone->nick == '') or ($zone->nick == null)) 
      $zone->nick = guifi_abbreviate($zone->nick);
    if (strlen($zone->nick.$edit['nick']) > 10)
      $nick = guifi_abbreviate($edit['nick']);
    else
      $nick = $edit['nick'];
    $ssid=$zone->nick.$nick;

    $radio=array();
    $radio['new']=true;
    $radio['id']=$edit['id'];
    $radio['nid']=$edit['nid'];
    $radio['model_id']=16;
    $radio['mode']=$edit['r']['newradio_mode'];
    $radio['protocol']='802.11b';
    $radio['channel']=0;
    $radio['antenna_gain']=14;
    $radio['antenna_azimuth']=0;
    if ($radio['mode'] == 'ap') {
      $radio['antenna_angle']=120;
      $radio['clients_accepted']="Yes";
      $radio['ssid']=$ssid.'AP'.$rc;
      if ($rc == 0)
        $radio['mac']=_guifi_mac_sum($edit['mac'],2);
      else
        $radio['mac']='';
    } else {
      $radio['antenna_angle']=30;
      $radio['clients_accepted']="No";
      $radio['ssid']=$ssid.'CPE'.$rc;
      if ($rc == 0)
        $radio['mac']=_guifi_mac_sum($edit['mac'],1);
      else
        $radio['mac']='';
    }

    $form_weight = 0;
    guifi_form_hidden($form,$edit,$form_weight);
    _guifi_radio_form($form,$radio,$rc,$form_weight);
    $action[2] = $rc;
    $action[3] = $tc;
    _guifi_device_buttons($form,$action,$form_weight);

    drupal_set_title(t('Create a radio at %dname',array('%dname'=> $edit['nick'])));

    return FALSE;
}

function _guifi_add_radio_submit(&$edit,$action) {
  guifi_log(GUIFILOG_NONE, "function _guifi_add_radio_submit()");

  $rc = $action[2];
  $tc = $action[3];

  $edit['radios'][$rc]['new']=true;
  $edit['radios'][$rc]['mode']=$edit['r']['newradio_mode'];
  $edit['radios'][$rc]['interfaces']=array();
  $edit['radios'][$rc]['interfaces'][0]=array();
  $edit['radios'][$rc]['interfaces'][0]['new']=true;
  switch ($edit['r']['newradio_mode']) {
    case 'ap':
      $edit['radios'][$rc]['interfaces'][0]['interface_type']='wds/p2p';
      // first radio, force wlan/Lan bridge and get an IP
      if ($tc == 0) {
        $edit['radios'][$rc]['interfaces'][1]=array();
        $edit['radios'][$rc]['interfaces'][1]['new']=true;
        $edit['radios'][$rc]['interfaces'][1]['interface_type']='wLan/Lan';
        $ips_allocated=guifi_get_ips('0.0.0.0','0.0.0.0',$edit);
        $net = guifi_get_subnet_by_nid($edit['nid'],'255.255.255.224','public',$ips_allocated);
        guifi_log(GUIFILOG_FULL,"IPS allocated: ".count($ips_allocated)." got net: ".$net.'/27');
        $edit['radios'][$rc]['interfaces'][1]['ipv4'][$rc]=array();
        $edit['radios'][$rc]['interfaces'][1]['ipv4'][$rc]['new']=true;
        $edit['radios'][$rc]['interfaces'][1]['ipv4'][$rc]['ipv4']=guifi_ip_op($net);
        guifi_log(GUIFILOG_FULL,"Assigned IP: ".$edit['radios'][$rc]['interfaces'][1]['ipv4'][$rc]['ipv4']);
        $edit['radios'][$rc]['interfaces'][1]['ipv4'][$rc]['netmask']='255.255.255.224';
      } 
      break;
    case 'client':
    case 'routedclient':
        $edit['radios'][$rc]['interfaces'][0]['new']=true;
        $edit['radios'][$rc]['interfaces'][0]['interface_type']='Wan';
      break;
  }
}

/* Delete interface */
function _guifi_delete_interface($edit,$op) {
  $parse=explode(',',$edit[edit_details]);

  switch ($op) {
  case t('Delete selected'):
    $output .= '<h2>'.t('Are you sure you want to delete this interface?').'</h2>'.$edit[radios][$parse[0]][ssid].' '.
									     $edit[radios][$parse[0]][interfaces][$parse[1]][interface_type];
$output .= '<br />'.form_button(t('Confirm delete'),'op').
		form_button(t('Back to list'),'op');
$output .= $message;
break;
case t('Confirm delete'):
if ($edit[radios][$parse[0]][interfaces][$parse[1]]['new'])
unset($edit[radios][$parse[0]][interfaces][$parse[1]]);
else
$output .= form_hidden('radios]['.$parse[0].'][interfaces]['.$parse[1].'][deleted',true);
$output .= '<h2>'.t('Interface deleted').'</h2>'.$link_text;
$output .= '<br />'.form_button(t('Back to list'),'op');
drupal_set_message(t('The interface %name has been deleted. To prevent accidental deletions, the delete will be confirmed only when you submit the changes.',array('%name' => theme('placeholder',$edit['radios'][$parse[0]]['ssid'].' '.$edit[radios][$parse[0]][interfaces][$parse[1]][interface_type]))));
break;
}
$output .= guifi_form_hidden('',$edit);
print theme('page',form($output));
}

/* _guifi_device_buttons(): Common function to add confirmation buttons */
function _guifi_device_buttons(&$form,$action,&$fweight = 100) {

  $action_str = implode(',',$action);

  $form['reset'] = array(
    '#type' => 'submit',
    '#parents' => array('op'),
    '#value' => t('Reset'),
    '#weight' => $fweight++,
  );
    $form['save_continue'] = array(
    '#type' => 'submit',
    '#parents' => array($action_str),
    '#name' => $action_str,
    '#value' => t('Save & continue edit'),
    '#weight' => $fweight++,
  );
  /* $form['accept'] = array(
    '#type' => 'submit',
    '#parents' => array($action_str),
    '#name' => $action_str,
    '#value' => t('Accept'),
    '#weight' => $fweight++,
  ); */
  $form['save_exit'] = array(
    '#type' => 'submit',
    '#parents' => array($action_str),
    '#name' => $action_str,
    '#value' => t('Save & exit'),
    '#weight' => $fweight++,
  );
}

/* Delete radio interface */
function _guifi_delete_radio_interface(&$form,&$edit,$action) {
  $radio_id=$action[2];
  $interface_id=$action[3];
  guifi_log(GUIFILOG_TRACE,sprintf('function _guifi_delete_radio_interface(radio: %d, interface: %d)',$radio_id,$interface_id));

  $fw = 0;
  guifi_form_hidden($form,$edit,$fw);
  $form['help'] = array(
    '#type' => 'item',
    '#title' => t('Are you sure you want to delete this interface?'),
    '#value' => $edit['radios'][$radio_id]['ssid'].'-'.
	$edit['radios'][$radio_id]['interfaces'][$interface_id]['interface_type'],
    '#description' => t('If you save at this point, this interface and links will be deleted, information saved and can\'t be undone.'),
    '#weight' => 0,
  );
  drupal_set_title(t('Delete radio interface (%type)',array('%type'=>$edit['radios'][$radio_id]['interfaces'][$interface_id]['interface_type'])));
  _guifi_device_buttons($form,$action);

  return FALSE;
}

function _guifi_delete_radio_interface_submit(&$edit,$action) {
  $radio_id=$action[2];
  $interface_id=$action[3];
  guifi_log(GUIFILOG_TRACE,sprintf('function _guifi_delete_radio_interface_submit(radio: %d, interface: %d)',$radio_id,$interface_id));
  $edit['radios'][$radio_id]['interfaces'][$interface_id]['deleted'] = true;
}

/* Delete radio interface IPv4 */
function _guifi_delete_radio_interface_ipv4(&$form,&$edit,$action) {
  $radio_id=$action[2];
  $interface_id=$action[3];
  $ipv4=str_replace('_','.',$action[4]);
  $mask=str_replace('_','.',$action[5]);
  guifi_log(GUIFILOG_TRACE,sprintf('function _guifi_delete_radio_interface_ipv4(radio: %d, interface: %d)',$radio_id,$interface_id));

  $fw = 0;
  guifi_form_hidden($form,$edit,$fw);

  $form['help'] = array(
    '#type' => 'item',
    '#title' => t('Are you sure you want to delete this interface?'),
    '#value' => $edit['radios'][$radio_id]['ssid'].'-'.
	$edit['radios'][$radio_id]['interfaces'][$interface_id]['interface_type'].'-'.
	$ipv4.'/'.$mask,
    '#description' => t('If you save at this point, this network addres and its links will be deleted, information saved and can\'t be undone.'),
  '#weight' => 0,
  );
  drupal_set_title(t('Delete radio interface (%type)',array('%type'=>$edit['radios'][$radio_id]['interfaces'][$interface_id]['interface_type'])));
  _guifi_device_buttons($form,$action);

  return FALSE;
}

function _guifi_delete_radio_interface_ipv4_submit(&$edit,$action) {
  $radio_id=$action[2];
  $interface_id=$action[3];
  guifi_log(GUIFILOG_TRACE,sprintf('function _guifi_delete_radio_interface_ipv4(radio: %d, interface: %d)',$radio_id,$interface_id));
  $edit['radios'][$radio_id]['interfaces'][$interface_id]['deleted'] = true;
}

/* Delete radio interface link */
function _guifi_delete_radio_interface_link(&$form,&$edit,$action) {
  $radio_id    =$action[2];
  $interface_id=$action[3];
  $ipv4_id     =$action[4];
  $link_id     =$action[5];
  $remote_nid  =$action[6];
  $remote_did  =$action[7];
  
  guifi_log(GUIFILOG_TRACE,sprintf('function _guifi_delete_radio_interface_link(radio: %d, interface: %d)',$radio_id,$interface_id),$action);

  $fw = 0;
  guifi_form_hidden($form,$edit,$fw);
  $form['help'] = array(
    '#type' => 'item',
    '#title' => t('Are you sure you want to delete this link?'),
    '#value' => $edit['radios'][$radio_id]['ssid'].'-'.
  	$edit['radios'][$radio_id]['interfaces'][$interface_id]['interface_type'].'-'.
	guifi_get_nodename($remote_nid).'/'.
	guifi_get_hostname($remote_did),
    '#description' => t('If you save at this point, this link will be deleted, information saved and can\'t be undone.'),
    '#weight' => 0,
  );
  drupal_set_title(t('Delete link (%type)',array('%type'=>$edit['radios'][$radio_id]['interfaces'][$interface_id]['interface_type'])));
  _guifi_device_buttons($form,$action);
  
  return FALSE;
}

function _guifi_delete_radio_interface_link_submit(&$edit,$action) {
  $radio_id=$action[2];
  $interface_id=$action[3];
  $ipv4_id     =$action[4];
  $link_id     =$action[5];
  $remote_nid  =$action[6];
  $remote_did  =$action[7];
  guifi_log(GUIFILOG_TRACE,sprintf('function _guifi_delete_radio_interface_link_submit(radio: %d, interface: %d)',$radio_id,$interface_id),$action);
  $edit['radios'][$radio_id]['interfaces'][$interface_id]['ipv4'][$ipv4_id]['links'][$link_id]['deleted'] = true;
}

/* Delete radio */
function _guifi_delete_radio(&$form,&$edit,$action) {
  guifi_log(GUIFILOG_TRACE,"function _guifi_delete_radio()");
  $radio_id=$action[2];
  $form_weight = 0;

  $form['help'] = array(
    '#type' => 'item',
    '#title' => t('Are you sure you want to delete this radio?'),
    '#value' => t('Radio #').$radio_id.' - '.$edit['radios'][$radio_id]['ssid'],
    '#description' => t('If you save at this point, radio and all its interfaces and links will be deleted, information saved and can\'t be undone.'),
    '#weight' => $form_weight++,
  );

  guifi_form_hidden($form,$edit,$form_weight);
  drupal_set_title(t('delete radio#').$radio_id.' - '.$edit['radios'][$radio_id]['ssid']);
  _guifi_device_buttons($form,$action,$form_weight);

  return FALSE;
}

function _guifi_delete_radio_submit(&$edit,$action) {
  guifi_log(GUIFILOG_TRACE,"function _guifi_delete_radio_submit()");
  $radio_id=$action[2];
  $edit['radios'][$radio_id]['deleted'] = true;
}

/* _guifi_link_2AP(): Link client to an AP */
function _guifi_link_2AP(&$form,&$edit,$action) {


  /*  print "Hola add Link 2AP!!\n<br>";
  print_r($action);
  print_r($edit['filters']);
  /*
  exit;

  /* read input parameters */
  $radio_id=$action[2];
  $interface_id=$action[3];

  $form_weight = 0;


  /*  if (!isset($edit['filters'])) {

  // get list of the current used ips
  $ips_allocated = guifi_get_ips('0.0.0.0','0.0.0.0',$edit);

  // Initializing variables
  $newlk = array();
  $newlk['new']=true;
  $newlk['link_type']='wds';
  $newlk['flag']='Planned';
  $newlk['link_type']='ap/client';
  $newlk['routing'] = 'Gateway';
  // if in mode AP
  if ($edit[radios][$radio_id]['mode'] == 'ap') {
    $base_ip[ipv4]=$edit[radios][$radio_id][interfaces][$interface_id][ipv4][$radio_id][ipv4];
    $base_ip[netmask]=$edit[radios][$radio_id][interfaces][$interface_id][ipv4][$radio_id][netmask];
    $item = _ipcalc($base_ip[ipv4],$base_ip[netmask]);
    $ip= guifi_next_ip($item['netid'],$base_ip[netmask],$ips_allocated);
    if ($ip == null) return;
      $newlk['interface'] = array();
    $newlk['interface'][interface_type] = 'Wan';
    $newlk['interface'][ipv4] = array();
    $newlk['interface'][ipv4]['new'] = true;
    $newlk['interface'][ipv4][ipv4] = $ip;
    $newlk['interface'][ipv4][netmask] = $base_ip[netmask];
  } else {
  // if in mode client
    $edit[radios][$radio_id][interfaces][$interface_id][ipv4][$radio_id][ipv4]='';
  }
  $edit[radios][$radio_id][interfaces][$interface_id][ipv4][$radio_id][links][]=$newlk;
  end($edit[radios][$radio_id][interfaces][$interface_id][ipv4][$radio_id][links]);
  $newlink_id = key($edit[radios][$radio_id][interfaces][$interface_id][ipv4][$radio_id][links]);
  $edit[edit_details]=implode(',',array($radio_id,$i_key,$radio_id,$newlink_id));
  }
*/

  // initialize filters
  if (empty($edit['filters'])) 
  $edit['filters'] = array(
    'dmin'   => 0,
    'dmax'   => 5,
    'search' => null,
    'type'   => 'ap/client',
    'mode'   => $edit['radios'][$radio_id]['mode'],
    'from_node' => $edit['nid'],
    'from_device' => $edit['id'],
    'from_radio' => $radio_id, 
    'azimuth' => "0,360",
  ); 


  // Filter form
  guifi_devices_select_filter($form,implode(',',$action),$edit['filters'],$form_weight);

  // Main select form
  $form['links'] = array(
    '#type' => 'select',
    '#parents'=> array('radios',$radio_id,'interfaces',$interface_id,'ipv4',$newlink_id,'links',$newlink_id,'linked'),
    '#title' => t('select the device which do you like to link with'),
    '#options' => guifi_devices_select($edit['filters']),
    '#description' => t('If you save at this point, link will be created and information saved.'),
    '#weight' => $form_weight++,
    );

  // Filter buttons
  _guifi_device_buttons($form,$action,$form_weight);

  drupal_set_title(t('Choose an AP from the list to link with %ssid',array('%ssid'=> $edit['radios'][$radio_id]['ssid'])));

  return FALSE;

}

/* _guifi_add_wds(): Add WDS/p2p link */
function _guifi_add_wds(&$form,&$edit,$action) {
  $radio_id=$action[2];
  $interface_id=$action[3];
  guifi_log(GUIFILOG_TRACE,sprintf("function _guifi_add_wds(Radio: %d, Interface: %d)",$radio_id,$interface_id));

  // read input parameters
  $form_weight = 0;

  guifi_form_hidden($form,$edit,$form_weight);

  // initialize filters
  if (empty($edit['filters']))
  $edit['filters'] = array(
    'dmin'   => 0,
    'dmax'   => 15,
    'search' => null,
    'type'   => 'wds',
    'mode'   => $edit['radios'][$radio_id]['mode'],
    'from_node' => $edit['nid'],
    'from_device' => $edit['id'],
    'from_radio' => $radio_id,
    'azimuth' => "0,360",
  );

  drupal_set_title(t(
    'Choose an AP from the list to link with %ssid',
    array(
      '%ssid'=> $edit['radios'][$radio_id]['ssid'])));

  // Filter form
  guifi_devices_select_filter(
    $form,
    implode(',',$action),
    $edit['filters'],
    $form_weight);

  $choices = guifi_devices_select($edit['filters']);

  if (count($choices) == 0) {
    $form['help'] = array(
      '#type' => 'item',
      '#parents'=> array('dummy'),
      '#title' => t('No devices available'),
      '#value'=> t('There are no devices to link within the given criteria, you can use the filters to get more results.'),
      '#description' => t('...or go back to the previous page'),
      '#weight' => 0,
    );
    return FALSE;
  }

  $form['links'] = array(
    '#type' => 'select',
    '#parents'=> array('linked'),
    '#title' => t('select the device which do you like to link with'),
    '#options' => $choices,
    '#description' => t('If you save at this point, link will be created and information saved.'),
    '#weight' => 0,
  );


  _guifi_device_buttons($form,$action,$form_weight);

  return FALSE;
}

function _guifi_add_wds_submit(&$edit,$action) {
  $radio_id=$action[2];
  $interface_id=$action[3];
  guifi_log(GUIFILOG_TRACE,sprintf("function _guifi_add_wds(Radio: %d, Interface: %d)",$radio_id,$interface_id));
  guifi_log(GUIFILOG_FULL,"linked",$edit['linked']);

  // get list of the current used ips
  $ips_allocated = guifi_get_ips('0.0.0.0','0.0.0.0',$edit);
  
  //
  // initializing WDS/p2p link parameters
  //
  $newlk['new']=true;
  $newlk['interface']=array();
  list($newlk['nid'],$newlk['device_id'],$newlk['interface']['radiodev_counter']) =  explode(',',$edit['linked']);
  guifi_log(GUIFILOG_FULL,"newlk",$newlk);
  $newlk['link_type']='wds';
  $newlk['flag']='Planned';
  $newlk['routing'] = 'BGP';
  // get an ip addres for local-remote interfaces
  $net = guifi_get_subnet_by_nid($edit['nid'],'255.255.255.252','backbone',$ips_allocated);
  $ip1 = guifi_ip_op($net);
  $ip2 = guifi_ip_op($ip1);
  guifi_merge_ip(array('ipv4'=>$ip1,'netmask'=>'255.255.255.252'),$ips_allocated,false);
  guifi_merge_ip(array('ipv4'=>$ip2,'netmask'=>'255.255.255.252'),$ips_allocated,true);
  // getting remote interface
  $remote_interface = db_fetch_array(db_query("SELECT id FROM {guifi_interfaces} WHERE device_id = %d AND interface_type = 'wds/p2p' AND radiodev_counter = %d",$newlk['device_id'],$newlk['interface']['radiodev_counter']));
  $newlk['interface']['id']=$remote_interface['id'];
  $newlk['interface']['device_id']=$newlk['device_id'];
  $newlk['interface']['interface_type']='wds/p2p';

  // remote ipv4
  $newlk['interface']['ipv4']=array();
  $newlk['interface']['ipv4']['new'] = true;
  $newlk['interface']['ipv4']['interface_id']=$remote_interface['id'];
  $newlk['interface']['ipv4']['ipv4']=$ip2;
  $newlk['interface']['ipv4']['netmask']='255.255.255.252';

  // initializing local interface
  $newif = array();
  $newif['new']=true;
  $newif['ipv4']=$ip1;
  $newif['netmask']='255.255.255.252';
  // agregating into the main array
  $newif['links'] = array();
  $newif['links'][] = $newlk;
  $edit['radios'][$radio_id]['interfaces'][$interface_id]['ipv4'][]=$newif;
}

?>
